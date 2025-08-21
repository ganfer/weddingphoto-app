const fileInput = document.getElementById('fileInput');
const selectedFiles = document.getElementById('selectedFiles');
const uploadStatus = document.getElementById('uploadStatus');
const gallery = document.getElementById('gallery'); // Galerie-Container

let uploading = false;

fileInput.addEventListener('change', () => {
    selectedFiles.innerHTML = '';
    Array.from(fileInput.files).forEach(file => {
        const div = document.createElement('div');
        div.textContent = file.name;
        selectedFiles.appendChild(div);
    });
});

const uploadForm = document.getElementById('uploadForm');
uploadForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (uploading) {
        uploadStatus.textContent = 'Bitte warten, Upload läuft noch...';
        return;
    }

    if (!fileInput.files.length) {
        uploadStatus.textContent = 'Keine Dateien ausgewählt.';
        return;
    }

    uploading = true;
    const formData = new FormData(uploadForm);

    uploadStatus.innerHTML = `<span class="spinner"></span> Hochladen...`;

    try {
        const res = await fetch('upload.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.uploaded && data.uploaded.length > 0) {
            uploadStatus.textContent = `Erfolgreich hochgeladen: ${data.uploaded.join(', ')}`;
            fileInput.value = '';
            selectedFiles.innerHTML = '';

            // Neue Bilder direkt oben einfügen
            data.uploaded.forEach(filename => {
                const img = document.createElement('img');
                img.src = `img/${filename}?t=${Date.now()}`; // Cache-Busting
                img.alt = filename;
                img.classList.add('gallery-img'); // falls du CSS-Klassen hast
                gallery.prepend(img); // oben einfügen
            });

        } else if (data.error) {
            uploadStatus.textContent = `Fehler: ${data.error}`;
        } else {
            uploadStatus.textContent = 'Upload abgeschlossen, aber keine Dateien hochgeladen.';
        }
    } catch (err) {
        uploadStatus.textContent = `Fehler beim Upload: ${err}`;
    } finally {
        uploading = false;
    }
});

