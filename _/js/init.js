$(document).ready(function() {

  if ($("html").hasClass("old-ie")) {
    return false;
  }
  
  // init carousel
  $('.slidewrap').carousel({
    slider: '.slider',
    slide: 'figure',
    slideHed: 'figcaption strong',
    nextSlide : '.next',
    prevSlide : '.prev',
    addPagination: false,
    addNav : true
  }).bind({

    // remove initial class to make 'next' arrow flash
    'carousel-beforemove' : function(e) {
      $("a.carousel-next").removeClass("init");
    },

    // update slide counter, remove flashing class (for keyboard nav)
    'carousel-aftermove' : function(e) {
      var slide = $("figure.carousel-active-slide").index()+1;
      $("a.carousel-prev, a.carousel-next").removeClass("flash");
      $("#counter").text(slide);
      window.location.hash = "slide-"+slide;
    }
  });

  // create slide counter
  $('<p/>')
    .attr('id','location')
    .html('<span id="counter">'+($("figure.carousel-active-slide").index()+1)+'</span>/'+$("figure").length)
    .appendTo('body');

  // about text
  $("aside").bind("click", function(){
    $(this).toggleClass("active");
  });

});