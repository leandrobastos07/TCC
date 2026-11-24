// JavaScript pequeno: apenas abre e fecha o menu em telas menores.
const botaoMenu = document.getElementById('menu-botao');
const menu = document.getElementById('menu');
if (botaoMenu && menu) {
    botaoMenu.addEventListener('click', function () {
        const aberto = menu.classList.toggle('aberto');
        botaoMenu.setAttribute('aria-expanded', aberto ? 'true' : 'false');
    });
}
