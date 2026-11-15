document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss success flash messages
    document.querySelectorAll('.message.success').forEach(msg => {
        setTimeout(() => {
            msg.style.transition = 'opacity 0.6s';
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 700);
        }, 4000);
    });

    // Confirm-password live match (register + profile change-password)
    const pwd = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');
    const newPwd = document.getElementById('new_password');
    const matchAgainst = pwd || newPwd;
    if (confirm && matchAgainst) {
        const check = () => {
            confirm.style.borderColor =
                confirm.value && confirm.value !== matchAgainst.value ? '#b3261e' : '';
        };
        confirm.addEventListener('input', check);
        matchAgainst.addEventListener('input', check);
    }

    // URL field sanity check on the dashboard add-form
    const urlInput = document.querySelector('input[name="url"]');
    if (urlInput) {
        urlInput.addEventListener('blur', () => {
            urlInput.style.borderColor =
                urlInput.value && !/^https?:\/\//i.test(urlInput.value) ? '#b3261e' : '';
        });
    }

    // Delete confirmation
    document.querySelectorAll('form[action="delete_media.php"]').forEach(form => {
        form.addEventListener('submit', e => {
            if (!confirm('Delete this item from your vault?')) {
                e.preventDefault();
            }
        });
    });

    // Avatar: validate type + size, preview, THEN submit
    const fileInput = document.querySelector('input[name="avatar"]');
    if (fileInput) {
        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (!file) return;

            if (!/^image\/(png|jpeg|webp|gif)$/.test(file.type)) {
                alert('Only JPG, PNG, WEBP or GIF images are allowed.');
                fileInput.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert('That image is larger than 2 MB.');
                fileInput.value = '';
                return;
            }

            const preview = document.querySelector('.avatar-lg');
            if (preview) {
                const reader = new FileReader();
                reader.onload = e => { preview.src = e.target.result; };
                reader.readAsDataURL(file);
            }
            fileInput.form.submit();
        });
    }
});
