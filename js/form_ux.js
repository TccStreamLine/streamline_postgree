document.addEventListener('DOMContentLoaded', function() {
    
    const passwordFields = document.querySelectorAll('input[type="password"]');
    passwordFields.forEach(field => {
        const wrapper = field.parentElement;
        if (getComputedStyle(wrapper).position === 'static') {
            wrapper.style.position = 'relative';
        }

        const toggleIcon = document.createElement('i');
        toggleIcon.classList.add('fas', 'fa-eye', 'password-toggle-icon');
        
        wrapper.appendChild(toggleIcon);

        toggleIcon.addEventListener('click', function() {
            const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
            field.setAttribute('type', type);
            
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    });

    const cardNumberInput = document.getElementById('card-number');
    const expiryDateInput = document.getElementById('validade');
    const cvcInput = document.getElementById('cvc');

    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').replace(/(\d{4})(?=\d)/g, '$1 ').trim();
        });
        cardNumberInput.addEventListener('keydown', function(e) {
            if (e.target.value.length >= 19 && !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(e.key)) {
                e.preventDefault();
            }
        });
    }

    if (expiryDateInput) {
        expiryDateInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });
         expiryDateInput.addEventListener('keydown', function(e) {
            if (e.target.value.length >= 5 && !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(e.key)) {
                e.preventDefault();
            }
        });
    }

    if (cvcInput) {
        cvcInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
         cvcInput.addEventListener('keydown', function(e) {
            if (e.target.value.length >= 4 && !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(e.key)) {
                e.preventDefault();
            }
        });
    }
});