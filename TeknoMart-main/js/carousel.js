// Retained for pages that use the legacy Bootstrap carousel.
document.addEventListener('DOMContentLoaded', function () {
  var element = document.getElementById('carouselExample');
  if (element && window.bootstrap && window.bootstrap.Carousel) {
    new window.bootstrap.Carousel(element, { interval: 4000, ride: 'carousel' });
  }
});
