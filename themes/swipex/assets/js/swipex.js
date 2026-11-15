document.addEventListener('DOMContentLoaded', () => {
  // Swipe Carousel Management
  const track = document.getElementById('swipeCarouselTrack');
  const dots = document.querySelectorAll('.carousel-dot');
  if (track && dots.length > 0) {
    let currentIndex = 0;
    const totalCards = dots.length;

    function updateCarousel(index) {
      currentIndex = Math.max(0, Math.min(index, totalCards - 1));
      const cards = track.querySelectorAll('.swipe-card-item');
      if (cards[currentIndex]) {
        cards[currentIndex].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
      }
      dots.forEach((dot, idx) => {
        if (idx === currentIndex) {
          dot.classList.add('bg-blue-600', 'w-6');
          dot.classList.remove('bg-slate-300', 'w-2');
        } else {
          dot.classList.remove('bg-blue-600', 'w-6');
          dot.classList.add('bg-slate-300', 'w-2');
        }
      });
    }

    dots.forEach((dot, idx) => {
      dot.addEventListener('click', () => updateCarousel(idx));
    });

    // Detect scroll position to update dots
    let scrollTimeout;
    track.addEventListener('scroll', () => {
      clearTimeout(scrollTimeout);
      scrollTimeout = setTimeout(() => {
        const cards = Array.from(track.querySelectorAll('.swipe-card-item'));
        const containerCenter = track.scrollLeft + track.offsetWidth / 2;
        let closestIndex = 0;
        let minDistance = Infinity;
        cards.forEach((card, idx) => {
          const cardCenter = card.offsetLeft + card.offsetWidth / 2;
          const dist = Math.abs(containerCenter - cardCenter);
          if (dist < minDistance) {
            minDistance = dist;
            closestIndex = idx;
          }
        });
        if (closestIndex !== currentIndex) {
          currentIndex = closestIndex;
          dots.forEach((dot, idx) => {
            if (idx === currentIndex) {
              dot.classList.add('bg-blue-600', 'w-6');
              dot.classList.remove('bg-slate-300', 'w-2');
            } else {
              dot.classList.remove('bg-blue-600', 'w-6');
              dot.classList.add('bg-slate-300', 'w-2');
            }
          });
        }
      }, 100);
    });
  }
});
