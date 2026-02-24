// Validation des formulaires
document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', function(e) {
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm_password');
        
        if(password && confirm && password.value !== confirm.value) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas');
        }
    });
});

// Confirmation avant actions
document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if(!confirm(this.dataset.confirm)) {
            e.preventDefault();
        }
    });
});
