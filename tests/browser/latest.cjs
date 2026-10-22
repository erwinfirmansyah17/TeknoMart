const { chromium }=require('playwright');
const path=require('node:path');
const assert=require('node:assert/strict');
(async()=>{
 const browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
 for(const width of [1440,390]){
  const context=await browser.newContext({viewport:{width,height:900},extraHTTPHeaders:{'X-Test-Role':'user','X-Test-Fixture':'commerce'}});
  const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto('http://127.0.0.1:8765/',{waitUntil:'networkidle'});
  assert.equal(await page.title(),'TeknoMart');
  assert.equal(await page.locator('.hero-slide').count(),1);
  assert.equal(await page.locator('.hero-slide.active').getAttribute('aria-hidden'),'false');
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),true);
  if(width<=820){
   const menu=page.locator('.store-menu-toggle');
   assert.equal(await menu.isVisible(),true);
   const box=await menu.boundingBox();assert.ok(box.x>width/2,'mobile menu should be on the right');
   const search=await page.locator('.nav-search').boundingBox();assert.ok(search.y>box.y,'search should be in the centered row beneath the brand');
   await menu.click();assert.equal(await menu.getAttribute('aria-expanded'),'true');
   assert.equal(await page.locator('.store-navigation').isVisible(),true);
   await page.keyboard.press('Escape');assert.equal(await page.locator('.store-navigation').isVisible(),false);
   await menu.click();
  }
  await page.screenshot({path:path.resolve(__dirname,`../screenshots/latest-header-${width}.png`)});
  await page.goto('http://127.0.0.1:8765/pages/profile.php',{waitUntil:'networkidle'});
  assert.equal(await page.locator('.profile-delete-form').count(),1);
  await page.goto('http://127.0.0.1:8765/pages/pesanan.php',{waitUntil:'networkidle'});
  assert.equal(await page.locator('.order-overview>a').count(),4);
  assert.equal(await page.locator('.review-reminder').count(),1);
  assert.match(await page.locator('.review-reminder').innerText(),/menunggu konfirmasi penerimaan dan ulasan/);
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),true);
  await page.screenshot({path:path.resolve(__dirname,`../screenshots/latest-orders-${width}.png`),fullPage:true});
  assert.deepEqual(errors,[]);
  console.log('PASS store header, mobile menu, order overview, review reminder',width);
  await context.close();
 }
 const carouselContext=await browser.newContext();
 const carouselPage=await carouselContext.newPage();
 await carouselPage.setContent('<section data-carousel data-interval="180"><article data-slide class="active" aria-hidden="false">Promo satu</article><article data-slide aria-hidden="true">Promo dua</article><button data-carousel-next aria-label="Berikutnya"></button><button data-carousel-prev aria-label="Sebelumnya"></button><button data-carousel-dot="0" aria-current="true"></button><button data-carousel-dot="1" aria-current="false"></button></section><script src="http://127.0.0.1:8765/js/app.js"></script>');
 await carouselPage.waitForTimeout(100);
 await carouselPage.getByRole('button',{name:'Berikutnya'}).click();
 assert.equal(await carouselPage.locator('[data-slide].active').innerText(),'Promo dua');
 await carouselPage.waitForTimeout(240);
 assert.equal(await carouselPage.locator('[data-slide].active').innerText(),'Promo satu');
 await carouselPage.locator('[data-carousel-dot="1"]').click();
 assert.equal(await carouselPage.locator('[data-slide].active').innerText(),'Promo dua');
 await carouselContext.close();
 console.log('PASS carousel manual control and automatic rotation');
 await browser.close();
})().catch(e=>{console.error(e);process.exitCode=1;});
