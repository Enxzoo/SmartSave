import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { randomBytes } from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';

const base = process.env.SMARTSAVE_URL || 'http://localhost/IM2_MIDTERM/';
const php = process.env.SMARTSAVE_PHP || 'C:/xampp/php/php.exe';
const email = `qa_browser_${randomBytes(6).toString('hex')}@example.test`;
const password = `Qa-${randomBytes(12).toString('hex')}`;
const today = new Date().toISOString().slice(0, 10);
const screenshots = fileURLToPath(new URL('screenshots/', import.meta.url));
await mkdir(screenshots, { recursive: true });
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const page = await context.newPage();
const errors = [];
const failedRequests = [];
page.on('pageerror', error => errors.push(error.message));
page.on('requestfailed', request => failedRequests.push({ url: request.url(), error: request.failure()?.errorText }));

async function visit(path) {
    await page.goto(new URL(path, base).href, { waitUntil: 'networkidle' });
    await page.evaluate(() => document.fonts.ready);
}

async function screenshot(name) {
    if (name.includes('modal')) {
        await page.locator('.modal.show').waitFor({ state: 'visible' });
        await page.waitForFunction(() => getComputedStyle(document.querySelector('.modal.show')).opacity === '1');
        await page.locator('.modal.show .modal-dialog').evaluate(element => Promise.all(element.getAnimations({ subtree: true }).map(animation => animation.finished)));
    }
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false, `${name} overflows horizontally`);
    assert.doesNotMatch(await page.locator('body').innerText(), /Fatal error|Warning:|Notice:|Connection failed:/, name);
    await page.screenshot({ path: `${screenshots}/${name}.png`, fullPage: true });
}

try {
    await visit('signup.php');
    await screenshot('signup-desktop');
    await page.locator('#name').fill('SmartSave QA Browser');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.locator('[data-password="password"]').click();
    assert.equal(await page.locator('#password').getAttribute('type'), 'text');
    await page.locator('[data-password="password"]').click();
    await page.locator('#confirm_password').fill(password);
    await page.getByRole('button', { name: 'Create Account', exact: true }).click();
    await page.waitForURL('**/dashboard.php');

    await page.getByRole('link', { name: 'Income', exact: true }).click();
    await page.getByRole('button', { name: 'Add Income', exact: true }).click();
    await page.locator('#incomeModal').waitFor({ state: 'visible' });
    await page.locator('#income_source').fill('Monthly salary');
    await page.locator('#income_amount').fill('12000.00');
    await page.locator('#income_date').fill(today);
    await page.getByRole('button', { name: 'Save Income', exact: true }).click();
    await page.waitForURL('**/income.php');
    await page.getByRole('button', { name: 'Edit income', exact: true }).click();
    assert.equal(await page.locator('#income_source').inputValue(), 'Monthly salary');
    await page.locator('#income_amount').fill('15000.00');
    await page.getByRole('button', { name: 'Save Income', exact: true }).click();
    await page.waitForURL('**/income.php');

    await page.getByRole('link', { name: 'Saving Goals', exact: true }).click();
    await page.getByRole('button', { name: 'Add Goal', exact: true }).click();
    await page.locator('#goalModal').waitFor({ state: 'visible' });
    await page.locator('#goal_name').fill('Emergency fund');
    await page.locator('#goal_target').fill('5000.00');
    await page.locator('#goal_current').fill('500.00');
    await page.locator('#goal_date').fill('2026-12-31');
    await page.getByRole('button', { name: 'Save Goal', exact: true }).click();
    await page.waitForURL('**/goals.php');
    await page.getByRole('button', { name: 'Add savings', exact: true }).click();
    await page.locator('#contribution_amount').fill('1000.00');
    await page.locator('#contributionModal').getByRole('button', { name: 'Add Savings', exact: true }).click();
    await page.waitForURL('**/goals.php');
    assert.match(await page.locator('.ss-table tbody').innerText(), /30%/);
    await page.getByRole('button', { name: 'Edit goal', exact: true }).click();
    assert.equal(await page.locator('#goal_current').inputValue(), '1500.00');
    await page.locator('#goalModal').getByRole('button', { name: 'Cancel', exact: true }).click();
    await page.locator('#goalModal').waitFor({ state: 'hidden' });

    await page.getByRole('link', { name: 'Expense', exact: true }).click();
    await page.getByRole('button', { name: 'Add Expense', exact: true }).click();
    await page.locator('#addExpenseModal').waitFor({ state: 'visible' });
    assert.equal(await page.locator('#addExpenseModal [name="user_id"]').getAttribute('readonly'), '');
    await page.locator('#addExpenseModal [name="description"]').fill('Groceries');
    await page.locator('#addExpenseModal [name="amount"]').fill('850.00');
    await page.locator('#addExpenseModal [name="date"]').fill(today);
    await page.locator('#addExpenseModal [name="category_id"]').selectOption({ index: 1 });
    await page.locator('#addExpenseModal').getByRole('button', { name: 'Add Expense', exact: true }).click();
    await page.waitForURL('**/expense.php');
    assert.match(await page.locator('.ss-table tbody').innerText(), /Groceries/);
    await page.getByRole('button', { name: 'Edit', exact: true }).click();
    assert.equal(await page.locator('#edit_description').inputValue(), 'Groceries');
    await page.locator('#edit_amount').fill('900.00');
    await page.getByRole('button', { name: 'Save Changes', exact: true }).click();
    await page.waitForURL('**/expense.php');

    for (const path of ['dashboard.php', 'income.php', 'expense.php', 'goals.php', 'account.php']) {
        await visit(path);
        await screenshot(path.replace('.php', '-desktop'));
        assert.equal(await page.locator('.ss-brand-mark i').evaluate(element => getComputedStyle(element, '::before').fontFamily), 'bootstrap-icons', 'Icon font must load');
    }
    await visit('income.php');
    await page.getByRole('button', { name: 'Add Income', exact: true }).click();
    assert.equal(await page.locator('#income_source').inputValue(), '', 'Add form clears edited record values');
    await screenshot('income-modal-desktop');
    await page.locator('#incomeModal').getByRole('button', { name: 'Cancel', exact: true }).click();
    await page.locator('#incomeModal').waitFor({ state: 'hidden' });

    await page.setViewportSize({ width: 390, height: 844 });
    for (const path of ['dashboard.php', 'income.php', 'expense.php', 'goals.php', 'account.php']) {
        await visit(path);
        await screenshot(path.replace('.php', '-mobile'));
    }
    await visit('income.php');
    await page.locator('#ssBurger').click();
    assert.match(await page.locator('#ssSidebar').getAttribute('class'), /ss-sidebar-open/);
    await page.getByRole('link', { name: 'Saving Goals', exact: true }).click();
    await page.waitForURL('**/goals.php');
    await page.getByRole('button', { name: 'Add Goal', exact: true }).click();
    await screenshot('goals-modal-mobile');
    await page.locator('#goalModal').getByRole('button', { name: 'Cancel', exact: true }).click();
    await page.locator('#goalModal').waitFor({ state: 'hidden' });

    await visit('income.php');
    await page.getByRole('button', { name: 'Delete income', exact: true }).click();
    await page.locator('#deleteRecordModal.show').waitFor();
    assert.equal(await page.locator('#delete_record_label').innerText(), 'Monthly salary');
    assert.ok(Number(await page.locator('#delete_record_id').inputValue()) > 0);
    await page.locator('#deleteRecordModal').getByRole('button', { name: 'Delete', exact: true }).click();
    await page.waitForURL('**/income.php');
    await page.getByText('No income records found.', { exact: true }).waitFor();
    await visit('expense.php');
    await page.getByRole('button', { name: 'Delete', exact: true }).click();
    await page.locator('#deleteExpenseModal.show').waitFor();
    assert.equal(await page.locator('#delete_description').innerText(), 'Groceries');
    assert.ok(Number(await page.locator('#delete_expenses_id').inputValue()) > 0);
    await page.locator('#deleteExpenseModal').getByRole('button', { name: 'Delete', exact: true }).click();
    await page.waitForURL('**/expense.php');
    await page.getByText('No expense records found.', { exact: true }).waitFor();
    await visit('goals.php');
    await page.getByRole('button', { name: 'Delete goal', exact: true }).click();
    await page.locator('#deleteRecordModal.show').waitFor();
    assert.equal(await page.locator('#delete_record_label').innerText(), 'Emergency fund');
    await page.locator('#deleteRecordModal').getByRole('button', { name: 'Delete', exact: true }).click();
    await page.waitForURL('**/goals.php');
    await page.getByText('No saving goals found.', { exact: true }).waitFor();

    await visit('account.php');
    await page.getByRole('button', { name: 'Log Out', exact: true }).click();
    await page.waitForURL('**/index.php');
    await screenshot('login-mobile');
    await page.setViewportSize({ width: 1440, height: 1000 });
    await screenshot('login-desktop');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.getByRole('button', { name: 'Log In', exact: true }).click();
    await page.waitForURL('**/dashboard.php');
    assert.deepEqual(errors, [], 'No browser JavaScript errors');
    console.log('PASS: browser signup/login/logout, navbar, add/edit/delete forms, saving contributions, icon assets, desktop/mobile screenshots, and no horizontal overflow.');
    console.log(`Screenshots: ${screenshots}`);
} catch (error) {
    await page.screenshot({ path: `${screenshots}/browser-failure.png`, fullPage: true });
    console.log('Page at failure:', page.url());
    console.log('Visible buttons:', await page.getByRole('button').allTextContents());
    console.log('Failed assets:', failedRequests);
    console.log('Bootstrap script:', await page.evaluate(() => typeof bootstrap));
    throw error;
} finally {
    await browser.close();
    const cleanup = spawnSync(php, [fileURLToPath(new URL('../cleanup.php', import.meta.url))], { input: JSON.stringify([email]), encoding: 'utf8' });
    assert.equal(cleanup.status, 0, cleanup.stderr || cleanup.stdout);
    console.log(cleanup.stdout.trim());
}
