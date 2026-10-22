const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
(async () => {
  const browser = await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
  const output = path.resolve(__dirname,'../screenshots');
  fs.mkdirSync(output,{recursive:true});
  const failures = [];
  for (const [name, route, role] of [['home','/',''],['catalog','/pages/product.php',''],['login','/pages/login.php',''],['cart','/pages/keranjang.php','user'],['profile','/pages/profile.php','user'],['orders','/pages/pesanan.php','user'],['dashboard','/bangjanganbang/index.php','admin'],['products','/bangjanganbang/all_product.php','admin'],['new-product','/bangjanganbang/add_product.php','admin']]) {
    for (const width of [1440,390]) {
      const context=await browser.newContext({viewport:{width,height:1000},extraHTTPHeaders:{'X-Test-Role':role}});
      const page=await context.newPage();
      page.on('pageerror',error=>failures.push(`${name} ${width}: ${error.message}`));
      await page.goto('http://127.0.0.1:8765'+route,{waitUntil:'networkidle'});
      // Load off-screen lazy images before checking/screenshotting the whole page.
      await page.locator('img[loading="lazy"]').evaluateAll(images => images.forEach(image => { image.loading = 'eager'; }));
      await page.waitForFunction(() => [...document.images].every(image => image.complete));
      await page.screenshot({path:path.join(output,`${name}-${width}.png`),fullPage:true});
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1);
      if(overflow) failures.push(`${name} ${width}: horizontal overflow`);
      const broken=await page.locator('img').evaluateAll(images=>images.filter(i=>!i.complete||!i.naturalWidth).length);
      if(broken) failures.push(`${name} ${width}: ${broken} broken images`);
      if(name==='dashboard' && width===390) {
        await page.getByRole('button',{name:'Buka navigasi'}).click();
        if(!(await page.locator('body').getAttribute('class')).includes('sidebar-open')) failures.push('mobile sidebar did not open');
        await page.keyboard.press('Escape');
      }
      console.log('CHECK',name,width,'overflow:',overflow,'broken images:',broken);
      await context.close();
    }
  }
  await browser.close();
  console.log(JSON.stringify({failures},null,2));
  process.exitCode=failures.length?1:0;
})().catch(error=>{console.error(error);process.exitCode=1;});
