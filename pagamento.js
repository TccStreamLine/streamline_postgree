document.addEventListener('DOMContentLoaded', function() {
    const cardNumberInput = document.getElementById('card-number');
    const expiryDateInput = document.getElementById('validade');
    const cvcInput = document.getElementById('cvc');

    // Máscara para o número do cartão (XXXX XXXX XXXX XXXX)
    cardNumberInput.addEventListener('input', function(e) {
        e.target.value = e.target.value
            .replace(/\D/g, '') // Remove tudo que não for dígito
            .replace(/(\d{4})(?=\d)/g, '$1 ') // Coloca um espaço a cada 4 dígitos
            .trim();
    });
     // Limita o tamanho do input
    cardNumberInput.addEventListener('keydown', function(e) {
        if (e.target.value.length >= 19 && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'ArrowLeft' && e.key !== 'ArrowRight' ) {
            e.preventDefault();
        }
    });

    // Máscara para a data de validade (MM/AA)
    expiryDateInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value;
    });
     // Limita o tamanho do input
    expiryDateInput.addEventListener('keydown', function(e) {
        if (e.target.value.length >= 5 && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') {
            e.preventDefault();
        }
    });

    // Limita o CVC a 3-4 dígitos
    cvcInput.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '');
    });
     cvcInput.addEventListener('keydown', function(e) {
        if (e.target.value.length >= 4 && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') {
            e.preventDefault();
        }
    });
});