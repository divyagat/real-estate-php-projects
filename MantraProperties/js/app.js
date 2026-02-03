let collaspe = document.querySelector("header .navbar");

/* window.onscroll = function () {
    if(document.documentElement.scrollTop > 400){
        collaspe.classList.add("fixed-top");
    }else{
        collaspe.classList.remove("fixed-top");
    }
}
 */

function numberToWords(number) {
  switch (number) {
    case 1:
      return 'one';
    case 2:
      return 'two';
    case 3:
      return 'three';
    case 4:
      return 'four';
    case 5:
      return 'five';
    case 6:
      return 'six';
    case 7:
      return 'seven';
    case 8:
      return 'eight';
    case 9:
      return 'nine';
    case 10:
      return 'ten';
    default:
      return ''; // Return an empty string for unsupported numbers
  }
}

$(document).ready(function () {
  /*************IF DROPDOWN MENU PRESENT IN HEADER THEN SORT CONTENT OF DROPDOWN MENU*************/

  $('.dropdown-menu').each(function () {
    var dropdownMenu = $(this);

    if ($(this).find('a').length > 8) {
      var aCount = $(this).find('a').length;
      var numColumns = Math.ceil(aCount / 8);
      var className = '';
      if (numColumns > 1) {
        className = numberToWords(numColumns) + '-cols';
      }
      $(this).addClass(className);
    }

    var listItems = dropdownMenu.find('a').get();

    listItems.sort(function (a, b) {
      var pattern = /[\r\t\n]\s{2,}/g;
      var textA = $.trim($(a).text());
      textA = textA.toUpperCase().replace(pattern, '');
      var textB = $.trim($(b).text());
      textB = textB.toUpperCase().replace(pattern, '');
      return (textA < textB) ? -1 : (textA > textB) ? 1 : 0; /*-FOR ASC ORDER-*/
      //return (textA < textB) ? 1 : (textA > textB) ? -1 : 0; /*-FOR DESC ORDER-*/
    });

    dropdownMenu.empty();

    $.each(listItems, function (index, listItem) {
      dropdownMenu.append(listItem);
    });
  });

  /*************IF DROPDOWN MENU PRESENT IN HEADER THEN SORT CONTENT OF DROPDOWN MENU*************/

  /*************FETCHING CONTENT FORM BLOG LISTING PAGE AND DISPLAY IT AS CAROUSEL IN HOME PAGE*************/

  var currentURL = window.location.href;
  var path = window.location.pathname;

  if (path != '' && path != '/') {
    currentURL = currentURL.replace(path, '');
  }
  if (currentURL.endsWith('/')) {
    currentURL = currentURL.slice(0, -1);
  }

  var SITEURL = currentURL + "/blog";

  if ($("#blogCarousel").length > 0) {
    $.ajax({
      url: SITEURL,
      method: "GET",
      dataType: "html",
      success: function (data) {
        var parsedContent = $.trim($(data).find(".row").html());
        console.log(parsedContent);
        if (parsedContent != '') {
          $("#blogCarousel").html(parsedContent);
          $("#blogCarousel").addClass('carousel-list owl-carousel owl-theme');
          $("#blogCarousel").closest('section').removeAttr("style");
          $('#blogCarousel').owlCarousel({
            loop: false,
            rewind: true,
            margin: 0,
            responsiveClass: true,
            dots: false,
            nav: true,
            autoplay: false,
            autoplayHoverPause: true,
            responsive: {
              0: {
                items: 1
              },
              600: {
                items: 2
              },
              1000: {
                items: 3
              },
            }
          });
        } else {
          $("#blogCarousel").closest('.blog .container').hide();
        }
      },
      error: function (xhr, status, error) {
        console.error("Error fetching content:", error);
      }
    });
  }

  /*************FETCHING CONTENT FORM BLOG LISTING PAGE AND DISPLAY IT AS CAROUSEL IN HOME PAGE*************/
  
});



//Video Carousal

function walkthroughCarousel() {

  if ($('#walkthroughVideo').find('iframe').length > 1) {

      $('#walkthroughVideo').owlCarousel({
          loop: false,
          dots: false,
          autoplay: false,
          margin: 10,
          autoplayHoverPause: true,
          responsiveClass: true,
          nav: true,
          responsive: {
              0: {
                  items: 1
              },
              991: {
                  items: 2
              },
          }
      })
  } else {
      $('#walkthroughVideo').addClass('no-owl-carousel');
  }
}

walkthroughCarousel();

$(window).scroll(function(){
  history.replaceState({}, document.title, location.href.substr(0, location.href.length-location.hash.length));
});