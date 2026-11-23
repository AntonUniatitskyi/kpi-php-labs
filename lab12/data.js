const STORAGE_KEY = 'pets-catalog';
const SPECIES_COLORS = {
  'Золота рибка': '#ff9a2e',
  'Неон': '#35c2ff',
  'Гуппі': '#ee4266',
  'Півник': '#b44cff',
  'Інше': '#7bd88f',
};

let pets = loadPets();

function loadPets() {
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY));
    if (Array.isArray(saved)) return saved;
  } catch (e) {  }
  return [
    { id: 1, name: 'Нео', species: 'Неон' },
    { id: 2, name: 'Золотко', species: 'Золота рибка' },
    { id: 3, name: 'Гуппі Макс', species: 'Гуппі' },
  ];
}

function savePets() {
  try { localStorage.setItem(STORAGE_KEY, JSON.stringify(pets)); } catch (e) {}
}