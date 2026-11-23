const listEl = document.querySelector('#list');
const countEl = document.querySelector('#count');

function render() {
  listEl.innerHTML = '';
  if (pets.length === 0) {
    listEl.innerHTML = '<li class="empty">Каталог порожній. Додайте першу тварину — і в акваріумі з\'явиться рибка.</li>';
  }
  for (const pet of pets) {
    const li = document.createElement('li');
    const dot = document.createElement('span');
    dot.className = 'dot';
    dot.style.background = SPECIES_COLORS[pet.species] || SPECIES_COLORS['Інше'];
    const name = document.createElement('strong');
    name.textContent = pet.name;                 // textContent — захист від XSS
    const meta = document.createElement('span');
    meta.className = 'meta';
    meta.textContent = pet.species;
    const del = document.createElement('button');
    del.type = 'button';
    del.className = 'ghost';
    del.textContent = 'Видалити';
    del.addEventListener('click', () => removePet(pet.id));
    li.append(dot, name, meta, del);
    listEl.append(li);
  }
  countEl.textContent = `Записів у каталозі: ${pets.length}. Рибок в акваріумі: ${Math.min(pets.length, MAX_FISH)}.`;
  syncFish(pets);                                // зв'язок даних з анімацією
}

function addPet(name, species) {
  pets.push({ id: Date.now(), name, species });
  savePets();
  render();
}

function removePet(id) {
  pets = pets.filter(p => p.id !== id);
  savePets();
  render();
}

document.querySelector('#add-form').addEventListener('submit', (e) => {
  e.preventDefault();
  const nameInput = document.querySelector('#name');
  const name = nameInput.value.trim();
  if (!name) return;
  addPet(name, document.querySelector('#species').value);
  nameInput.value = '';
  nameInput.focus();
});

render();
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) startAquarium();
else stopAquarium();