// for menu
(function ($) {
            $(document).ready(function () {
                var menuLeft = $('#test-menu-left').slideMenu({
                    position: 'left',
                    submenuLinkAfter: ' >',
                    backLinkBefore: '< '
                });
                var menuRight = $('#test-menu-right').slideMenu({
                    submenuLinkAfter: ' â‡’',
                    backLinkBefore: 'â‡ '
                });
            });
        })(jQuery);
            $(document).ready(function() {
        $('#client-logos').owlCarousel({
        loop:true,
        margin:15,
        nav:true,
        responsive:{
        0:{
        items:1
        },
        600:{
        items:1
        },
        1000:{
        items:3
        }
        },
                navText: ["<img src='assets/img/left-owl-icon.png'/>","<img src='assets/img/right-owl-icon.png'/>"]
        });
        });
// gallery
    // $(document).ready(function(){
    //     setTimeout(function() {
    //         $('#rcmodalsp').modal();
    //     }, 1000);
    //     });
$(document).ready(function(){
    $(".gl-filter-button").click(function(){
        var value = $(this).attr('data-filter');

        if(value == "all")
        {
            //$('.gl-filter').removeClass('hidden');
            $('.gl-filter').show('1000');
        }
        else
        {
//            $('.gl-filter[filter-item="'+value+'"]').removeClass('hidden');
//            $(".gl-filter").not('.filter[filter-item="'+value+'"]').addClass('hidden');
            $(".gl-filter").not('.'+value).hide('3000');
            $('.gl-filter').filter('.'+value).show('3000');

        }
    });

    if ($(".filter-button").removeClass("active")) {
$(this).removeClass("active");
}
$(this).addClass("active");
});
// gallery end
 AOS.init({
  duration: 1200,
})
//MCG AWARDS Start
            $(document).ready(function() {
        $('#mcg-awards').owlCarousel({
        loop:true,
        margin:15,
        nav:true,
        responsive:{
        0:{
        items:1
        },
        600:{
        items:2
        },
        1000:{
        items:3
        }
        },
        navText: ["<img src='assets/img/left-owl-icon.png'/>","<img src='assets/img/right-owl-icon.png'/>"]
        });
        });
//MCG AWARDS END
window.onscroll = function() {myFunction()};
var header = document.getElementById("myHeadersp");
var sticky = header.offsetTop;
function myFunction() {
  if (window.pageYOffset > sticky) {
    header.classList.add("stickysp");
  } else {
    header.classList.remove("stickysp");
  }
}
// floor slider
  $(document).ready(function () {
            $(".floor-rvslider").owlCarousel({
                items:1,
                loop:true,
                nav:true,
                autoplay:false,
                navText: ['<span class="dbl-icn" aria-label="Previous"><img src="assets/img/left-slide-icon.png"></span>','<span class="dbl-icn" aria-label="Next"><img src="assets/img/right-slide-icon.png"></span>'],
                lazyLoad:true,
                  animateIn: 'fadeIn',

            });
        });
  // gallery slider
  $(document).ready(function () {
            $(".gallery-owl-slide").owlCarousel({
                items:1,
                loop:true,
                nav:true,
                autoplay:false,
                navText: ['<span class="gll-icn" aria-label="Previous"><hr><span>PREV</span></span>','<span class="gll-icn" aria-label="Next"><span>NEXT</span><hr></span>'],
                lazyLoad:true,

            });
        });
// mcg slider
        $(document).ready(function () {
            $(".mcg-rvslider").owlCarousel({
                items:1,
                loop:true,
                nav:true,
                autoplay:true,
                navText: ['<span aria-label="Previous">â€¹</span>','<span aria-label="Next">â€º</span>'],
        lazyLoad:true,
            });
        });

        // home slider
 $(document).ready(function () {
            $('.home-rvslider').owlCarousel({
                items:1,
                loop:true,
                nav:true,
                autoplay:false,
                navText: ['<span aria-label="Previous">â€¹</span>','<span aria-label="Next">â€º</span>'],
        lazyLoad:true,
        // animateOut: 'fadeOut',
            });
        });
        //owl jquery fancy
// $().fancybox({ selector : '.owl-item:not(.cloned) a' });
$.fancybox.defaults.loop = true;
//back to top
$(document).ready(function(){
    $(window).scroll(function(){
        if ($(this).scrollTop() > 100) {
            $('#scrollrv').fadeIn();
        } else {
            $('#scrollrv').fadeOut();
        }
    });
    $('#scrollrv').click(function(){
        $("html, body").animate({ scrollTop: 0 }, 600);
        return false;
    });
});
$(document).ready(function(){
// setTimeout(function() {
//     $('#homemodalauto').modal();
// }, 135000);
});
// accordian
$("#accordion").on("hide.bs.collapse show.bs.collapse", (e) => {
  $(e.target).prev().find("i:last-child").toggleClass("fa-minus fa-plus");
});
$('.gallery-btn-container button').click(function() {
    $('.current').removeClass('current');
    $(this).addClass('current');
    });

//gmaps
    $(document).ready(function(){
        $(window).scroll(function(){
            if ($(this).scrollTop() > 100) {
                $('.g-button').css('display', 'flex');
            }
        });
    });

//     document.onscroll = function() {
//     if (window.innerHeight + window.scrollY > document.body.clientHeight) {
//         document.getElementById('g-button').style.display='none';
//     }else{
//         document.getElementById('g-button').style.display='flex';
//     }
// }
$(document).ready(function(){
var h = $('#myHeadersp').outerHeight();
$(".nav-scroll").click(function(e) {

    e.preventDefault();
    var elem = $(this);
    var id = elem.attr('href');
    scrollToID(id, h);
});

var scrollToID = function(id, h) {
    // console.log(h);
    $('html, body').animate({
        scrollTop: $(id).offset().top - h
    }, 300);
    return true;
}
});

// $(document).ready(function() {
//     // secHeight();
//     // in order to "reset" back to the top
//     $("html, body").scrollTop(0);

//     var getUrlParameter = function getUrlParameter(sParam) {
//       var sPageURL = window.location.search.substring(1),
//           sURLVariables = sPageURL.split('&'),
//           sParameterName,
//           i;
//       for (i = 0; i < sURLVariables.length; i++) {
//           sParameterName = sURLVariables[i].split('=');
//           if (sParameterName[0] === sParam) {
//               return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
//           }
//       }
//       return false;
//   };

//     var h = $('#myHeadersp').outerHeight();
//     var section = getUrlParameter("page-section");
//     // // var brouchure = getUrlParameter("brouchure");
//     console.log(section);
//     // section.has('page-section') // true
//     // let pageParam = section.get('page-section');
//     if (section && h !== undefined) {
//         $('html, body').animate({
//             scrollTop: $( "#" + section ).offset().top - h
//         }, 500);
//     //   if ($(window).width() > 960) {

//     //     // console.log(brouchure);
//     //     var window_h = $(window).height();
//     //     var window_h_h = window_h - h;
//     //     var section_h = $('#' + section).outerHeight();
//     //     var final_h = (window_h_h - section_h) / 2;
//     //     var gg = h + final_h;
//     //     $('html, body').animate({
//     //           scrollTop: $( "#" + section ).offset().top - gg
//     //     }, 300);
//     // }
//     //   else {
//     //     $('html, body').animate({
//     //         scrollTop: $( "#" + section ).offset().top - h
//     //     }, 300);
//     // }
//   }
//   });