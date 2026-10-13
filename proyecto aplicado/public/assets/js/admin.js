(() => {
    'use strict';

    const input = document.getElementById('foto');
    const image = document.getElementById('foto-preview-imagen');
    const empty = document.getElementById('foto-preview-vacio');
    const preview = document.getElementById('foto-preview');
    const status = document.getElementById('foto-estado');
    if (!input || !image || !empty || !preview || !status) return;

    const originalSource = image.getAttribute('src');
    let objectUrl = null;

    const restoreOriginal = () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        if (originalSource) image.setAttribute('src', originalSource);
        else image.removeAttribute('src');
        image.hidden = !originalSource;
        empty.hidden = Boolean(originalSource);
        preview.classList.toggle('has-photo', Boolean(originalSource));
    };

    input.addEventListener('change', () => {
        restoreOriginal();
        status.textContent = '';
        const file = input.files?.[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            status.textContent = 'Selecciona una fotografía JPG, PNG o WebP.';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            status.textContent = 'Esta fotografía supera los 5 MB. Selecciona una imagen más pequeña.';
            return;
        }
        objectUrl = URL.createObjectURL(file);
        image.src = objectUrl;
        image.hidden = false;
        empty.hidden = true;
        preview.classList.add('has-photo');
        status.textContent = 'Fotografía seleccionada: ' + file.name + '. Se guardará al enviar el formulario.';
    });

    window.addEventListener('pagehide', () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
    });
})();
