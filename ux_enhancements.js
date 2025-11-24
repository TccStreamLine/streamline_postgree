document.addEventListener('DOMContentLoaded', function() {
    // Encontra todos os inputs de senha na página
    const passwordFields = document.querySelectorAll('input[type="password"]');

    passwordFields.forEach(field => {
        // Garante que o campo de senha esteja dentro de um container relativo
        const wrapper = field.parentElement;
        if (getComputedStyle(wrapper).position === 'static') {
            wrapper.style.position = 'relative';
        }

        // Cria o ícone do "olhinho"
        const toggleIcon = document.createElement('i');
        toggleIcon.classList.add('fas', 'fa-eye', 'password-toggle-icon');
        
        // Adiciona o ícone ao lado do campo de senha
        wrapper.appendChild(toggleIcon);

        // Adiciona o evento de clique ao ícone
        toggleIcon.addEventListener('click', function() {
            // Alterna o tipo do input entre 'password' e 'text'
            const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
            field.setAttribute('type', type);
            
            // Alterna o ícone do olho (aberto/fechado)
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    });
});