const { chromium } = require('playwright');
const path = require('node:path');
(async()=>{
  const browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
  const checks=[];
  for(const width of [1440,390]) {
    const context=await browser.newContext({viewport:{width,height:1000},extraHTTPHeaders:{'X-Test-Role':'user','X-Test-Fixture':'commerce'}});
    const page=await context.newPage();
    page.on('pageerror',e=>{throw e;});
    await page.goto('http://127.0.0.1:8765/pages/keranjang.php');
    const selection=page.locator('[data-cart-price]');
    await selection.uncheck();
    if(await page.locator('[data-cart-total]').textContent()!=='Rp 0') throw Error('Cart total not recalculated');
    await selection.check();
    await page.screenshot({path:path.resolve(__dirname,`../screenshots/cart-filled-${width}.png`),fullPage:true});
    await page.getByRole('button',{name:'Lanjut checkout'}).click();
    await page.waitForURL('**/rincian.php');
    await page.locator('select[name=jasa_kirim]').selectOption('1');
    await page.locator('select[name=metode]').selectOption('1');
    if(await page.locator('[data-checkout-total]').textContent()!=='Rp 222.000') throw Error('Checkout total incorrect');
    if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1)) throw Error('Checkout overflows viewport');
    await page.screenshot({path:path.resolve(__dirname,`../screenshots/checkout-${width}.png`),fullPage:true});
    for(const [name,route] of [['detail','deskripsi.php?id=1'],['payment','pembayaran.php?id=1'],['review','rating.php?id=4'],['refund','refund.php?id=4']]) {
      await page.goto('http://127.0.0.1:8765/pages/'+route,{waitUntil:'networkidle'});
      if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1)) throw Error(`${name} overflows viewport`);
      await page.screenshot({path:path.resolve(__dirname,`../screenshots/${name}-${width}.png`),fullPage:true});
      checks.push(`${name} ${width}`);
    }
    await page.goto('http://127.0.0.1:8765/pages/login.php');
    await page.locator('[data-show-password]').check();
    if(await page.locator('input[name=password]').getAttribute('type')!=='text') throw Error('Password toggle failed');
    await context.close();
    checks.push(`cart selection, checkout totals, password toggle ${width}`);
  }
  // Verify deployment under Laragon's actual nested URL as well as root hosting.
  const context=await browser.newContext({viewport:{width:1440,height:1000}});
  const page=await context.newPage();
  await page.goto('http://localhost/TeknoMart-main/TeknoMart-main/',{waitUntil:'networkidle'});
  const background=await page.locator('.hero').evaluate(el=>getComputedStyle(el).backgroundColor);
  if(background==='rgba(0, 0, 0, 0)') throw Error('Nested deployment stylesheet missing');
  checks.push('Laragon nested deployment stylesheet');
  await context.close(); await browser.close();
  checks.forEach(c=>console.log('PASS',c));
})().catch(e=>{console.error(e);process.exitCode=1;});
