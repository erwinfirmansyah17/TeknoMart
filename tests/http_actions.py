"""Real form POSTs, isolated by temporary MySQL tables in tests/fixtures.php."""
import base64
import http.cookiejar
import json
import re
import urllib.error
import urllib.parse
import urllib.request
import uuid
from pathlib import Path

BASE='http://127.0.0.1:8765'
ROOT=Path(__file__).resolve().parents[1]
results=[]
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args,**kwargs): return None

class Client:
    def __init__(self,role='user'):
        self.role=role
        self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NoRedirect)
    def request(self,path,data=None,files=None):
        headers={'X-Test-Role':self.role,'X-Test-Fixture':'commerce'}
        body=None
        if files:
            boundary=uuid.uuid4().hex
            body=b''
            for key,value in data.items():
                body+=f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode()
            for key,filename,payload,mime in files:
                body+=f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"; filename="{filename}"\r\nContent-Type: {mime}\r\n\r\n'.encode()+payload+b'\r\n'
            body+=f'--{boundary}--\r\n'.encode()
            headers['Content-Type']='multipart/form-data; boundary='+boundary
        elif data is not None:
            body=urllib.parse.urlencode(data,doseq=True).encode()
            headers['Content-Type']='application/x-www-form-urlencoded'
        request=urllib.request.Request(BASE+path,data=body,headers=headers)
        try: response=self.opener.open(request)
        except urllib.error.HTTPError as ex: response=ex
        text=response.read().decode(errors='replace')
        if re.search(r'(Warning|Fatal error|Deprecated|Notice|Parse error):',text): raise AssertionError(text[:1500])
        state=json.loads(base64.b64decode(response.headers['X-Test-State']))
        return response.status,text,state,response.headers
    def form(self,path,data,files=None):
        _,text,_,_=self.request(path)
        token=re.search(r'name="csrf" value="([a-f0-9]+)"',text)
        assert token, 'No CSRF field at '+path
        return self.request(path,{'csrf':token[1],**data},files)

def test(name,callback):
    try:
        assert callback()
        results.append((name,True))
    except Exception as ex:
        results.append((name,False))
        print('ERROR',name,str(ex)[:700])
    print('PASS' if results[-1][1] else 'FAIL',name)

def product(state,pid=1): return next(p for p in state['products'] if p['id_barang']==pid)
def order(state,oid=1): return next(o for o in state['orders'] if o['id_orders']==oid)

def login(password,expected):
    code,body,state,_=Client('').form('/pages/login.php',{'username':'test-user','password':password})
    return (code==302 and state['userSession']==1) if expected else (code==200 and state['userSession'] is None and 'Username atau password salah' in body)
test('Customer login success',lambda:login('test-password',True))
test('Customer wrong password blocked',lambda:login('wrong',False))
test('Admin legacy login success',lambda:Client('').form('/bangjanganbang/login_admin.php',{'username':'test-admin','password':'legacy-password'})[2]['adminSession']==1)
test('Registration succeeds under strict SQL mode',lambda:len(Client('').form('/pages/register.php',{'username':'new-user','nama':"New O'Neil",'alamat':'New address','email':'new@example.invalid','password':'new-password','confirm':'new-password'})[2]['users'])==3)
test('Duplicate registration blocked',lambda:'sudah digunakan' in Client('').form('/pages/register.php',{'username':'test-user','nama':'Name','alamat':'Address','email':'new@example.invalid','password':'new-password','confirm':'new-password'})[1])
test('CSRF missing blocks profile update',lambda:Client().request('/pages/profile.php',{'nama':'Changed','telp':'1','alamat':'A'})[2]['users'][0]['nama']=='Test User')
test('Profile apostrophe saved safely',lambda:Client().form('/pages/profile.php',{'nama':"O'Neil",'telp':'08012345','alamat':'New address'})[2]['users'][0]['nama']=="O'Neil")
test('Change password requires current password',lambda:'tidak sesuai' in Client().form('/pages/ubah_pw.php',{'current_password':'wrong','password':'new-password','confirm':'new-password'})[1])
test('Change password accepts valid confirmation',lambda:Client().form('/pages/ubah_pw.php',{'current_password':'test-password','password':'new-password','confirm':'new-password'})[0]==302)
test('Product add-to-cart uses session owner',lambda:next(c for c in Client().form('/pages/deskripsi.php?id=1',{'action':'cart','qty':2,'id_user':2})[2]['cart'] if c['id_user']==1)['qty']==4)
test('Cart removal leaves other customer cart',lambda:Client().form('/pages/keranjang.php',{'action':'remove','id':1})[2]['cart']==[{'id_barang':1,'id_user':2,'qty':3}])
test('Cart excessive quantity blocked',lambda:'melebihi stok' in Client().form('/pages/keranjang.php',{'action':'update','id':1,'qty':999})[1])

shipping={'nama':'Test User','telp':'0800000001','alamat':'Test address','jasa_kirim':1,'metode':1,'pesan':'Test checkout','harga_satuan':1,'id_user':2}
def cart_checkout():
    c=Client()
    c.form('/pages/keranjang.php',{'action':'checkout','selected[]':1})
    code,_,state,_=c.form('/pages/rincian.php',shipping)
    return code==302 and len(state['orders'])==8 and state['orders'][-1]['id_user']==1 and product(state)['stok']==8 and len(state['cart'])==1 and state['cart'][0]['id_user']==2
test('Cart checkout reserves stock and preserves other cart',cart_checkout)
def buy_now():
    c=Client()
    c.form('/pages/deskripsi.php?id=1',{'action':'buy','qty':3})
    code,_,state,_=c.form('/pages/rinciannow.php',shipping)
    return code==302 and len(state['orders'])==8 and product(state)['stok']==7 and state['details'][-1]['harga_satuan']==100000
test('Buy-now uses authoritative price and quantity',buy_now)
test('Order owned by another customer returns 404',lambda:Client().request('/pages/pembayaran.php?id=7')[0]==404)
test('Customer cancellation restores reserved stock',lambda:product(Client().form('/pages/batal.php?id=1',{'action':'cancel'})[2])['stok']==11)
test('GET cancellation does not mutate order',lambda:order(Client().request('/pages/batal.php?id=1')[2])['status']=='Belum Bayar')
png=(ROOT/'TeknoMart-main/assets/icon/shop.png').read_bytes()
test('Profile photo persists for authenticated user',lambda:Client().form('/pages/profile.php',{'nama':'Test User','telp':'0800000001','alamat':'Test address'},[('profile_photo','avatar.png',png,'image/png')])[2]['profiles'][0]['id_user']==1)
test('Profile photo can be deleted safely',lambda:Client().form('/pages/profile.php',{'action':'delete_photo'})[2]['profiles']==[])
test('Payment image upload updates order once',lambda:order(Client().form('/pages/pembayaran.php?id=1',{},[('bukti','proof.png',png,'image/png')])[2])['status']=='Menunggu Konfirmasi')
test('Executable upload rejected',lambda:'Gunakan gambar' in Client().form('/pages/pembayaran.php?id=1',{},[('bukti','proof.php',b'<?php echo 1;','application/octet-stream')])[1])
test('Review submission completes delivered order',lambda:order(Client().form('/pages/rating.php?id=4',{'ratings[1]':5,'reviews[1]':"It's great"})[2],4)['status']=='Selesai')
test('Invalid review rejected',lambda:'Rating harus' in Client().form('/pages/rating.php?id=4',{'ratings[1]':6,'reviews[1]':'Bad rating'})[1])
test('Refund application saves proof and status',lambda:order(Client().form('/pages/refund.php?id=4',{'alasan':'Item damaged'},[('bukti','proof.png',png,'image/png')])[2],4)['status']=='Diajukan Retur')
test('Admin confirms only payment-pending orders',lambda:order(Client('admin').form('/bangjanganbang/konfirmasi.php',{'action':'confirm','id':2})[2],2)['status']=='Diproses')
test('Admin ships processed order',lambda:order(Client('admin').form('/bangjanganbang/diproses.php',{'action':'ship','id':3})[2],3)['status']=='Dikirim')
test('Admin rejects unpaid confirmation',lambda:order(Client('admin').form('/bangjanganbang/konfirmasi.php',{'action':'confirm','id':1})[2],1)['status']=='Belum Bayar')
test('Admin refund acceptance persists',lambda:Client('admin').form('/bangjanganbang/refund.php',{'action':'accept_refund','id':5})[2]['refunds'][0]['status_refund']=='Diterima')
test('Admin stock addition records actual cost',lambda:Client('admin').form('/bangjanganbang/add_product.php?id=1&mode=stock',{'action':'stock','id':1,'stok':4,'harga_beli':70000})[2]['expenses'][-1]=={'harga':70000,'qty':4})
product_data={'action':'save','nama_barang':"Test's product",'kategori':'Laptop','merk':'Test','keterangan':'Detailed specifications','harga':150000,'harga_beli':90000,'stok':2}
test('Admin creates product and expense with valid image',lambda:len(Client('admin').form('/bangjanganbang/add_product.php',product_data,[('img','product.png',png,'image/png')])[2]['products'])==3)
test('Admin edits product without replacing image',lambda:product(Client('admin').form('/bangjanganbang/edit_product.php?id=1',{**product_data,'id':1})[2])['nama_barang']=="Test's product")
test('Product with order history cannot be deleted',lambda:len(Client('admin').form('/bangjanganbang/all_product.php',{'action':'delete','id':1})[2]['products'])==2)
test('Unused product can be deleted',lambda:len(Client('admin').form('/bangjanganbang/all_product.php',{'action':'delete','id':2})[2]['products'])==1)
test('CSV includes end-of-day transaction',lambda:'100000' in Client('admin').request('/bangjanganbang/revenue.php?tanggal_awal=2026-09-30&tanggal_akhir=2026-09-30')[1])
test('Logout clears only customer identity',lambda:Client().form('/pages/logout.php',{})[2]['userSession'] is None)
print(f'{len(results)} tests, {sum(not ok for _,ok in results)} failures')
raise SystemExit(any(not ok for _,ok in results))
