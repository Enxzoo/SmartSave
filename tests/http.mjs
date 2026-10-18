import assert from 'node:assert/strict';
import { randomBytes } from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const base = process.env.SMARTSAVE_URL || 'http://localhost/IM2_MIDTERM/';
const php = process.env.SMARTSAVE_PHP || 'C:/xampp/php/php.exe';
const tag = randomBytes(6).toString('hex');
const emails = [`qa_http_a_${tag}@example.test`, `qa_http_b_${tag}@example.test`];
const password = `Qa-${randomBytes(12).toString('hex')}`;
const nextPassword = `New-${randomBytes(12).toString('hex')}`;

class Client {
    cookies = new Map();
    async request(path, data) {
        const response = await fetch(new URL(path, base), {
            method: data ? 'POST' : 'GET', redirect: 'manual',
            headers: { Cookie: [...this.cookies].map(([key, value]) => `${key}=${value}`).join('; '), ...(data ? { 'Content-Type': 'application/x-www-form-urlencoded' } : {}) },
            ...(data ? { body: new URLSearchParams(data) } : {}),
        });
        for (const cookie of response.headers.getSetCookie()) {
            const [name, ...value] = cookie.split(';')[0].split('=');
            this.cookies.set(name, value.join('='));
        }
        const html = await response.text();
        assert.doesNotMatch(html, /Fatal error|Warning:|Notice:|SQLSTATE|Connection failed:/, path);
        return { status: response.status, location: response.headers.get('location'), html, response };
    }
    async token(path) {
        const page = await this.request(path);
        assert.equal(page.status, 200, path);
        const token = page.html.match(/name="csrf_token" value="([a-f0-9]+)"/);
        assert.ok(token, `CSRF token in ${path}`);
        return token[1];
    }
}

const a = new Client();
const b = new Client();
try {
    for (const path of ['dashboard.php', 'income.php', 'expense.php', 'goals.php', 'account.php', 'backend/expense_code.php']) {
        const result = await a.request(path);
        assert.equal(result.status, 303);
        assert.match(result.location, /index\.php$/);
    }
    const login = await new Client().request('');
    assert.equal(login.status, 200, 'The root URL directly serves the login page.');
    assert.match(login.html, /SmartSave - Log In/);
    assert.ok(login.response.headers.getSetCookie().some(cookie => /HttpOnly/i.test(cookie) && /SameSite=Lax/i.test(cookie)));
    const originalSession = a.cookies.get('PHPSESSID');
    let token = await a.token('signup.php');
    let result = await a.request('signup.php', { csrf_token: token, name: 'SmartSave QA A', email: emails[0], password, confirm_password: password });
    assert.equal(result.status, 303);
    assert.match(result.location, /dashboard\.php$/);
    assert.notEqual(a.cookies.get('PHPSESSID'), originalSession, 'Authentication rotates the session ID.');
    for (const path of ['dashboard.php', 'income.php', 'expense.php', 'goals.php', 'account.php']) {
        assert.equal((await a.request(path)).status, 200, path);
    }
    token = await b.token('signup.php');
    result = await b.request('signup.php', { csrf_token: token, name: 'SmartSave QA B', email: emails[0], password, confirm_password: password });
    assert.equal(result.status, 200);
    assert.match(result.html, /already registered/);
    result = await b.request('signup.php', { csrf_token: token, name: 'SmartSave QA B', email: emails[1], password, confirm_password: password });
    assert.equal(result.status, 303);
    token = await a.token('income.php');
    assert.equal((await a.request('income.php', { action: 'save_income', source: 'No CSRF', amount: '100.00', date: '2026-10-13' })).status, 403);
    result = await a.request('income.php', { csrf_token: token, action: 'save_income', income_id: '', source: 'QA salary', amount: '1000.00', date: '2026-10-13' });
    assert.equal(result.status, 303);
    let page = await a.request('income.php');
    assert.match(page.html, /QA salary/);
    const incomeId = page.html.match(/data-delete-id="(\d+)"/)[1];
    assert.doesNotMatch((await b.request('income.php')).html, /QA salary/);
    let bToken = await b.token('income.php');
    result = await b.request('income.php', { csrf_token: bToken, action: 'save_income', income_id: incomeId, source: 'Hacked income', amount: '1.00', date: '2026-10-13' });
    assert.match(result.html, /no longer available/);
    result = await a.request('income.php', { csrf_token: token, action: 'save_income', income_id: incomeId, source: 'QA salary updated', amount: '1500.00', date: '2026-10-13' });
    assert.equal(result.status, 303);
    token = await a.token('goals.php');
    result = await a.request('goals.php', { csrf_token: token, action: 'save_goal', goal_id: '', goal_name: 'QA emergency fund', target_amount: '100.00', current_amount: '0.00', target_date: '2026-12-31' });
    assert.equal(result.status, 303);
    page = await a.request('goals.php');
    const goalId = page.html.match(/data-goal-id="(\d+)"/)[1];
    result = await a.request('goals.php', { csrf_token: token, action: 'contribute', goal_id: goalId, amount: '60.00' });
    assert.equal(result.status, 303);
    page = await a.request('goals.php');
    assert.match(page.html, /60%/);
    assert.doesNotMatch(page.html, /ss-status-complete">Completed/);
    result = await a.request('goals.php', { csrf_token: token, action: 'contribute', goal_id: goalId, amount: '40.01' });
    assert.match(result.html, /exceeds the amount remaining/);
    result = await a.request('goals.php', { csrf_token: token, action: 'contribute', goal_id: goalId, amount: '40.00' });
    assert.equal(result.status, 303);
    assert.match((await a.request('goals.php')).html, /ss-status-complete">Completed/);
    token = await a.token('expense.php');
    page = await a.request('expense.php');
    const categoryId = page.html.match(/<option value="(\d+)">/)[1];
    result = await a.request('backend/expense_code.php', { csrf_token: token, addExpense: '1', description: 'QA expense', amount: '20.00', date: '2026-10-13', category_id: categoryId, user_id: '1' });
    assert.equal(result.status, 303);
    page = await a.request('expense.php');
    assert.match(page.html, /QA expense/);
    const expenseId = page.html.match(/data-id="(\d+)"/)[1];
    assert.doesNotMatch((await b.request('expense.php')).html, /QA expense/);
    bToken = await b.token('expense.php');
    await b.request('backend/expense_code.php', { csrf_token: bToken, deleteExpense: expenseId });
    assert.match((await b.request('expense.php')).html, /no longer available/);
    assert.match((await a.request('expense.php')).html, /QA expense/);
    assert.equal((await a.request(`backend/expense_code.php?deleteExpense=${expenseId}`)).status, 403);
    result = await a.request('backend/expense_code.php', { csrf_token: token, updateExpense: '1', expenses_id: expenseId, description: 'QA expense updated', amount: '25.00', date: '2026-10-13', category_id: categoryId, user_id: '1' });
    assert.equal(result.status, 303);
    const dashboard = await a.request('dashboard.php');
    assert.match(dashboard.html, /1,475\.00/);
    assert.match((await a.request('income.php?keyword=updated')).html, /QA salary updated/);
    assert.doesNotMatch((await a.request('expense.php?keyword=notfound')).html, /QA expense updated/);
    token = await a.token('account.php');
    result = await a.request('account.php', { csrf_token: token, action: 'update_profile', name: 'SmartSave QA A Updated', email: emails[0], current_password: password });
    assert.equal(result.status, 303);
    assert.match((await a.request('dashboard.php')).html, /SmartSave QA A Updated/);
    result = await a.request('account.php', { csrf_token: token, action: 'change_password', current_password: password, new_password: nextPassword, confirm_password: nextPassword });
    assert.equal(result.status, 303);
    await a.request('backend/expense_code.php', { csrf_token: token, deleteExpense: expenseId });
    await a.request('income.php', { csrf_token: token, action: 'delete_income', record_id: incomeId });
    await a.request('goals.php', { csrf_token: token, action: 'delete_goal', record_id: goalId });
    assert.doesNotMatch((await a.request('income.php')).html, /QA salary updated/);
    assert.doesNotMatch((await a.request('expense.php')).html, /QA expense updated/);
    assert.doesNotMatch((await a.request('goals.php')).html, /QA emergency fund/);
    result = await a.request('logout.php', { csrf_token: token });
    assert.equal(result.status, 303);
    assert.equal((await a.request('dashboard.php')).status, 303);
    token = await a.token('index.php');
    result = await a.request('index.php', { csrf_token: token, email: emails[0], password });
    assert.match(result.html, /email or password is incorrect/);
    result = await a.request('index.php', { csrf_token: token, email: emails[0], password: nextPassword });
    assert.equal(result.status, 303);
    console.log('PASS: signup, login, session rotation, logout, CSRF, account isolation, CRUD, goal limits, profile/password changes, search, and dashboard totals.');
} finally {
    const cleanup = spawnSync(php, [fileURLToPath(new URL('cleanup.php', import.meta.url))], { input: JSON.stringify(emails), encoding: 'utf8' });
    assert.equal(cleanup.status, 0, cleanup.stderr || cleanup.stdout);
    console.log(cleanup.stdout.trim());
}
