import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = new URL('../', import.meta.url);
const documents = [
    '01-product-and-status.fa.md',
    '02-erp-and-pos.fa.md',
    '03-customers-and-crm.fa.md',
    '04-storefront.fa.md',
    '05-engineering-and-design.fa.md',
    '06-roadmap-and-handoff.fa.md',
];

const index = readFileSync(new URL('docs/product/README.md', root), 'utf8');
const review = index.split('\n').find((line) => line.startsWith('آخرین بازبینی:'));
if (!review) throw new Error('Product documentation review metadata is missing.');

const introduction = `# مرجع کامل محصول ویرا — یکپارچه با جهش

${review}

این سند مستقل برای ارسال به یک چت یا همکار جدید است؛ برای شناخت محصول به فایل دیگری وابسته نیست. محتوای آن از شش سند مرجع داخل docs/product ساخته شده است. برای تغییر پایدار، ابتدا همان سندهای تفکیکی را ویرایش و سپس دستور زیر را از ریشهٔ پروژه اجرا کنید:

\`node scripts/build-product-context.mjs\`

**قاعدهٔ اصلی:** «هدف محصول» با «قابلیت پیاده‌شده» تفاوت دارد. فقط زیرساخت آغازین ساخته شده است؛ جزئیات عملیاتی ERP، CRM، صندوق و خرید اینترنتی، مشخصات توسعهٔ آینده هستند. تصمیم‌های باز و پیشنهادها را توافق قطعی یا قابلیت آماده تلقی نکنید.

رمز، توکن و دادهٔ واقعی مشتری در این سند قرار ندارد. برای شروع چت جدید، متن‌های آمادهٔ بخش آخر را به همراه همین فایل بفرستید.
`;

const sections = documents.map((name) => {
    const content = readFileSync(new URL(`docs/product/${name}`, root), 'utf8');
    return content.trim().replace(/^(#{1,5}) /gm, '$1# ');
});

const output = new URL('VIRA-PROJECT-CONTEXT.fa.md', root);
writeFileSync(output, `${introduction.trim()}\n\n---\n\n${sections.join('\n\n---\n\n')}\n`, 'utf8');
console.log(`Product context built: ${fileURLToPath(output)}`);
