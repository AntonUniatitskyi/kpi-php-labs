const canvas = document.querySelector('#aquarium');
const ctx = canvas.getContext('2d');
const toggleBtn = document.querySelector('#toggle');
const MAX_FISH = 30;
let fishList = [];
let rafId = null;
let time = 0;

function createFish(pet) {
  return {
    id: pet.id,
    color: SPECIES_COLORS[pet.species] || SPECIES_COLORS['Інше'],
    x: Math.random() * canvas.width,
    baseY: 40 + Math.random() * (canvas.height - 80),
    speed: 0.6 + Math.random() * 1.2,
    amplitude: 10 + Math.random() * 25,
    frequency: 0.015 + Math.random() * 0.015,
    phase: Math.random() * Math.PI * 2,
    size: 13 + Math.random() * 13,
    dir: Math.random() < 0.5 ? 1 : -1,
  };
}

function syncFish(records) {
  const wanted = records.slice(0, MAX_FISH);
  const byId = new Map(fishList.map(f => [f.id, f]));
  fishList = wanted.map(pet => {
    const fish = byId.get(pet.id) || createFish(pet);
    fish.color = SPECIES_COLORS[pet.species] || SPECIES_COLORS['Інше'];
    return fish;
  });
  if (rafId === null) drawFrame();
}

function update() {
  time += 1;
  for (const f of fishList) {
    f.x += f.speed * f.dir;
    if (f.dir === 1 && f.x > canvas.width + f.size * 2) f.x = -f.size * 2;
    if (f.dir === -1 && f.x < -f.size * 2) f.x = canvas.width + f.size * 2;
  }
}

function drawFish(f) {
  const arg = f.x * f.frequency + f.phase;
  const y = f.baseY + Math.sin(arg) * f.amplitude;
  const slope = Math.cos(arg) * f.amplitude * f.frequency;
  const angle = Math.atan(slope * f.dir);

  ctx.save();
  ctx.translate(f.x, y);
  ctx.scale(f.dir, 1);
  ctx.rotate(f.dir === 1 ? angle : -angle);

  const wag = Math.sin(time * 0.2 + f.phase) * 0.3;
  ctx.fillStyle = f.color;
  ctx.beginPath();
  ctx.moveTo(-f.size * 0.8, 0);
  ctx.lineTo(-f.size * 1.5, -f.size * (0.5 + wag));
  ctx.lineTo(-f.size * 1.5, f.size * (0.5 - wag));
  ctx.closePath();
  ctx.fill();

  ctx.beginPath();
  ctx.ellipse(0, 0, f.size, f.size * 0.6, 0, 0, Math.PI * 2);
  ctx.fill();

  ctx.fillStyle = '#fff';
  ctx.beginPath();
  ctx.arc(f.size * 0.5, -f.size * 0.15, f.size * 0.18, 0, Math.PI * 2);
  ctx.fill();
  ctx.fillStyle = '#000';
  ctx.beginPath();
  ctx.arc(f.size * 0.55, -f.size * 0.15, f.size * 0.08, 0, Math.PI * 2);
  ctx.fill();
  ctx.restore();
}

function drawFrame() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  fishList.forEach(drawFish);
}

function loop() {
  update();
  drawFrame();
  rafId = requestAnimationFrame(loop);
}

function startAquarium() {
  if (rafId !== null) return;
  rafId = requestAnimationFrame(loop);
  toggleBtn.textContent = 'Стоп';
}

function stopAquarium() {
  cancelAnimationFrame(rafId);
  rafId = null;
  toggleBtn.textContent = 'Старт';
}

toggleBtn.addEventListener('click', () => rafId === null ? startAquarium() : stopAquarium());

let wasRunning = true;
new IntersectionObserver(([entry]) => {
  if (!entry.isIntersecting) {
    wasRunning = rafId !== null;
    stopAquarium();
  } else if (wasRunning) {
    startAquarium();
  }
}).observe(canvas);

if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  wasRunning = false;
}