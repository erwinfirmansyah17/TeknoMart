"""Read-only HTTP checks against tests/router.php (127.0.0.1:8765)."""
import json
import re
import subprocess
import urllib.error
import urllib.request
from pathlib import Path

root = Path(__file__).resolve().parents[1]
php = """<?php
require 'TeknoMart-main/includes/bootstrap.php';
$u=one('SELECT id_user FROM tb_user ORDER BY id_user LIMIT 1');
echo json_encode(['product'=>one('SELECT id_barang FROM tb_barang LIMIT 1')['id_barang']??0,'order'=>one('SELECT id_orders FROM orders WHERE id_user=? LIMIT 1',[(int)($u['id_user']??0)])['id_orders']??0]);
"""
meta = json.loads(subprocess.run(['php', '-d', 'session.save_path=tests/sessions'], input=php, text=True, capture_output=True, cwd=root, check=True).stdout)
cases = []
for p in (root / 'TeknoMart-main/pages').glob('*.php'):
    if p.stem in ['deskripsi']:
        suffix = '?id=' + str(meta['product'])
    elif p.stem in ['pembayaran', 'rating', 'refund', 'batal']:
        suffix = '?id=' + str(meta['order'])
    else:
        suffix = ''
    cases.append(('/pages/' + p.name + suffix, 'user'))
for p in (root / 'TeknoMart-main/bangjanganbang').glob('*.php'):
    suffix = '?tanggal_awal=2024-01-01&tanggal_akhir=2026-12-31' if p.stem in ['pendapatan','pengeluaran','revenue','outcome'] else ''
    cases.append(('/bangjanganbang/' + p.name + suffix, 'admin'))
cases += [('/', ''), ('/pages/product.php?page=-10', ''), ('/pages/product.php?kategori=%27', ''), ('/pages/search_product.php?search_query=%27%20OR%201%3D1', ''), ('/pages/search_product.php?search_query=%3Cscript%3Ealert(1)%3C/script%3E',''), ('/pages/deskripsi.php?id=9999999', ''), ('/pages/pembayaran.php?id=9999999', 'user'), ('/bangjanganbang/pendapatan.php?tanggal_awal=2026-02-30', 'admin'), ('/bangjanganbang/edit_product.php?id='+str(meta['product']), 'admin'), ('/bangjanganbang/add_product.php?mode=stock&id='+str(meta['product']), 'admin')]
failures=[]
for path, role in cases:
    request=urllib.request.Request('http://127.0.0.1:8765'+path, headers={'X-Test-Role':role})
    try:
        response=urllib.request.urlopen(request, timeout=15)
    except urllib.error.HTTPError as ex:
        response=ex
    content=response.read().decode('utf-8-sig',errors='replace')
    errors=re.findall(r'(?:Warning|Fatal error|Deprecated|Notice|Parse error):[^<\n]*',content)
    bad = bool(errors) or response.status>=500 or (response.status==404 and '9999999' not in path)
    if '<script>alert(1)</script>' in content: bad=True
    print(('FAIL' if bad else 'PASS'),response.status,path)
    if bad: failures.append((path,errors))

# Guard checks must redirect guests before any content is rendered.
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl): return None
opener=urllib.request.build_opener(NoRedirect)
for path in ['/pages/keranjang.php','/pages/pembayaran.php?id=1','/pages/profile.php','/bangjanganbang/all_product.php','/bangjanganbang/revenue.php','/bangjanganbang/user.php']:
    try: response=opener.open('http://127.0.0.1:8765'+path)
    except urllib.error.HTTPError as ex: response=ex
    ok=response.status==302 and 'login' in response.headers.get('Location','')
    print('PASS' if ok else 'FAIL','guest guard',path)
    if not ok: failures.append((path,'missing auth redirect'))
print(f'{len(cases)+6} checks, {len(failures)} failures')
for failure in failures: print(failure)
raise SystemExit(bool(failures))
