const path = require('path');
const fs = require('fs');
const puppeteer = require('puppeteer');

(async () => {
  try {
    const projectRoot = path.resolve(__dirname, '..');
    const htmlPath = path.join(projectRoot, 'manual_usuario_fondas.html');
    if (!fs.existsSync(htmlPath)) {
      console.error('No se encontró el archivo manual_usuario_fondas.html en:', htmlPath);
      process.exit(1);
    }

    const fileUrl = 'file:///' + htmlPath.replace(/\\/g, '/');

    const browser = await puppeteer.launch({ args: ['--no-sandbox', '--disable-setuid-sandbox'] });
    const page = await browser.newPage();
    await page.goto(fileUrl, { waitUntil: 'networkidle0' });
    await page.emulateMediaType('screen');

    const outputPdf = path.join(projectRoot, 'manual_usuario_fondas.pdf');
    await page.pdf({ path: outputPdf, format: 'A4', printBackground: true, margin: { top: '20mm', bottom: '20mm', left: '15mm', right: '15mm' } });

    console.log('PDF generado en:', outputPdf);
    await browser.close();
    process.exit(0);
  } catch (err) {
    console.error('Error generando PDF:', err);
    process.exit(2);
  }
})();
