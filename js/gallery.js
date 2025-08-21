document.addEventListener("DOMContentLoaded", () => {
  const gallery = document.getElementById("gallery");
  const lightbox = document.getElementById("lightbox");
  const lightboxImg = lightbox.querySelector("img");
  const prevBtn = document.getElementById("prev");
  const nextBtn = document.getElementById("next");

  let images = [];
  let currentIndex = 0;
  const preloadedImages = [];

  // Galerie laden
  fetch("gallery.php")
    .then(res => res.json())
    .then(data => {
      images = data;
      images.forEach((img, i) => {
        // Galerie-Bild
        const el = document.createElement("img");
        el.src = img.src;
        el.alt = img.filename;
        el.addEventListener("click", () => openLightbox(i));
        gallery.appendChild(el);

        // Lightbox-Bild preloaden
        const preload = new Image();
        preload.src = img.src;
        preloadedImages[i] = preload;
      });
    })
    .catch(err => console.error("Fehler beim Laden der Galerie:", err));

  // Lightbox öffnen
  function openLightbox(index) {
    currentIndex = index;
    lightbox.style.display = "flex";
    lightboxImg.src = preloadedImages[currentIndex].src; // sofort geladenes Bild
  }

  // Lightbox schließen
  function closeLightbox() {
    lightbox.style.display = "none";
  }

  // Vorheriges Bild
  function showPrev() {
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    lightboxImg.src = preloadedImages[currentIndex].src;
  }

  // Nächstes Bild
  function showNext() {
    currentIndex = (currentIndex + 1) % images.length;
    lightboxImg.src = preloadedImages[currentIndex].src;
  }

  // Event-Listener
  prevBtn.addEventListener("click", showPrev);
  nextBtn.addEventListener("click", showNext);

  // Hintergrund anklicken schließt Lightbox
  lightbox.addEventListener("click", e => {
    if (e.target === lightbox) closeLightbox();
  });

  // Tastatursteuerung
  document.addEventListener("keydown", (e) => {
    if (lightbox.style.display === "flex") {
      if (e.key === "ArrowLeft") {
        showPrev();
      } else if (e.key === "ArrowRight") {
        showNext();
      } else if (e.key === "Escape") {
        closeLightbox();
      }
    }
  });
});
