const results = document.getElementById('results');
const search = document.getElementById('search');
const progressBar = document.getElementById('progress');
const progressText = document.getElementById('progress-text');

let total = 0;
let answered = 0;

let debounceTimer = null;
let requestCounter = 0;

function updateProgress() {
    const percent = total ? Math.round((answered / total) * 100) : 0;
    progressBar.value = percent;
    progressText.textContent = `${answered} з ${total} (${percent}%)`;
}

function showContainerError(text) {
    results.replaceChildren();
    const box = document.createElement('div');
    box.className = 'error-box';
    box.textContent = text;
    results.appendChild(box);
}

function setMessage(el, text, kind) {
    el.textContent = text;
    el.className = 'msg ' + kind;
}

async function loadQuestions(term = '') {
    const current = ++requestCounter;
    try {
        const response = await fetch('api_list.php?q=' + encodeURIComponent(term));
        if (!response.ok) {
            throw new Error('Сервер повернув помилку ' + response.status);
        }
        const data = await response.json();
        if (current !== requestCounter) return;

        if (term === '') {
            total = data.length;
            answered = data.filter(q => q.answered).length;
            updateProgress();
        }
        render(data);
    } catch (err) {
        if (current !== requestCounter) return;
        const reason = err instanceof TypeError ? 'немає зв\'язку із сервером' : err.message;
        showContainerError('Не вдалося завантажити питання: ' + reason);
    }
}


function render(questions) {
    results.replaceChildren();

    if (questions.length === 0) {
        const p = document.createElement('p');
        p.textContent = 'Нічого не знайдено.';
        results.appendChild(p);
        return;
    }
    questions.forEach(q => results.appendChild(buildCard(q)));
}

function buildField(type) {
    let field;

    if (type === 'rating') {
        field = document.createElement('select');
        field.append(new Option('— оберіть оцінку —', ''));
        for (let i = 1; i <= 5; i++) field.append(new Option(String(i), String(i)));
    } else if (type === 'yesno') {
        field = document.createElement('select');
        field.append(new Option('— оберіть —', ''));
        field.append(new Option('Так', 'Так'));
        field.append(new Option('Ні', 'Ні'));
    } else {
        field = document.createElement('input');
        field.type = 'text';
        field.maxLength = 500;
        field.placeholder = 'Ваша відповідь';
    }
    field.required = true;
    return field;
}

function markAnswered(card, field, button, msg) {
    card.classList.add('done');
    field.disabled = true;
    button.disabled = true;
    button.textContent = 'Надіслано';
    setMessage(msg, 'Відповідь надіслано ✓', 'ok');
}

function buildCard(q) {
    const card = document.createElement('div');
    card.className = 'card';
    card.dataset.id = q.id;

    const title = document.createElement('h3');
    title.textContent = q.text;

    const badge = document.createElement('span');
    badge.className = 'badge';
    badge.textContent = q.type;

    const form = document.createElement('form');
    const field = buildField(q.type);
    const button = document.createElement('button');
    button.type = 'submit';
    button.textContent = 'Відправити';
    form.append(field, button);

    const msg = document.createElement('p');
    msg.className = 'msg';

    form.addEventListener('submit', event => {
        event.preventDefault();
        submitAnswer(q, card, field, button, msg);
    });

    if (q.answered) {
        markAnswered(card, field, button, msg);
    }

    card.append(title, badge, form, msg);
    return card;
}

async function submitAnswer(q, card, field, button, msg) {
    const value = field.value.trim();
    if (!value) {
        setMessage(msg, 'Заповніть відповідь', 'error');
        return;
    }

    button.disabled = true;
    setMessage(msg, 'Надсилаю...', 'ok');

    try {
        const response = await fetch('api_add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ question_id: q.id, answer_text: value })
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(data.error || 'Помилка сервера ' + response.status);
        }

        total = data.total;
        answered = data.answered;
        updateProgress();
        q.answered = true;
        markAnswered(card, field, button, msg);
    } catch (err) {
        const reason = err instanceof TypeError ? 'немає зв\'язку із сервером' : err.message;
        setMessage(msg, 'Не вдалося надіслати: ' + reason, 'error');
        button.disabled = false;
    }
}


search.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadQuestions(search.value.trim()), 250);
});

loadQuestions();