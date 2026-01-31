class ResourceLoader {
  loadCSS(e) {
    return new Promise((r, a) => {
      let o = document.createElement("link");
      (o.rel = "stylesheet"),
        (o.type = "text/css"),
        (o.href = e),
        (o.onload = () => r(`CSS loaded from ${e}`)),
        (o.onerror = (r) => a(`Failed to load CSS from ${e}: ${r}`)),
        document.head.appendChild(o);
    });
  }
  loadJS(e) {
    return new Promise((r, a) => {
      let o = document.createElement("script");
      (o.src = e),
        (o.type = "text/javascript"),
        (o.onload = () => r(`JavaScript loaded from ${e}`)),
        (o.onerror = (r) => a(`Failed to load JavaScript from ${e}: ${r}`)),
        document.body.appendChild(o);
    });
  }
}

class ScrollSpy {
  constructor(t, e, s = 60) {
    (this.nav = document.querySelector(t)),
      (this.sections = document.querySelectorAll(e)),
      (this.navLinks = this.nav.querySelectorAll("a")),
      (this.offset = s),
      this.init();
  }
  init() {
    this.handleScroll(), this.addEventListeners();
  }
  handleScroll() {
    window.addEventListener("scroll", () => {
      let t = this.getCurrentSection();
      this.highlightNavLink(t);
    });
  }
  getCurrentSection() {
    let t = null;
    return (
      this.sections.forEach((e) => {
        let s = e.offsetTop - this.offset,
          i = s + e.offsetHeight;
        window.scrollY >= s && window.scrollY < i && (t = e);
      }),
      t
    );
  }
  highlightNavLink(t) {
    this.navLinks.forEach((e) => {
      e.classList.remove("active"),
        t &&
          e.getAttribute("href").slice(1) === t.id &&
          e.classList.add("active");
    });
  }
  addEventListeners() {
    this.navLinks.forEach((t) => {
      t.addEventListener("click", (e) => {
        e.preventDefault(), this.scrollToSection(t.getAttribute("href"));
      });
    });
  }
  scrollToSection(t) {
    let e = document.querySelector(t);
    window.scrollTo({ top: e.offsetTop - this.offset, behavior: "smooth" });
  }
}

const loadBaseFont = () => document.documentElement.classList.add("pg-loaded");

const loadTelInput = (countryCode) => {
  const phoneInputs = document.querySelectorAll('input[type="tel"]');
  phoneInputs.forEach((input) => {
    const iti = intlTelInput(input, {
      initialCountry: countryCode,
      separateDialCode: true,
    });

    const isoCodeInput = document.createElement("input");
    isoCodeInput.type = "hidden";
    isoCodeInput.name = `${input.name}_iso_code`;
    isoCodeInput.id = `${input.id}_iso_code`;

    const dialCodeInput = document.createElement("input");
    dialCodeInput.type = "hidden";
    dialCodeInput.name = `${input.name}_dial_code`;
    dialCodeInput.id = `${input.id}_dial_code`;

    input.parentElement.appendChild(isoCodeInput);
    input.parentElement.appendChild(dialCodeInput);

    input.addEventListener("input", () => {
      const countryData = iti.getSelectedCountryData();
      isoCodeInput.value = countryData.iso2;
      dialCodeInput.value = countryData.dialCode;
    });

    const countryData = iti.getSelectedCountryData();
    isoCodeInput.value = countryData.iso2;
    dialCodeInput.value = countryData.dialCode;
  });
};

const loadFavicon = (e) => {
  if (document.getElementById("favicon")) {
    const favicon = document.getElementById("favicon");
    favicon.href =
      "https://s3.ap-south-1.amazonaws.com/microsites.images/microsite/images/favicons/favicon-" +
      e +
      ".png";
  }
};

function loadChatgen(showSmallChatWindow) {
  // var e=window.ChatGen||[];let t=new URLSearchParams(window.location.search),n={};for(let[a,i]of t)-1==["server","key"].indexOf(a)&&(n[a]=i);if(window.chatgenSettings||(window.chatgenSettings={}),window.chatgenSettings={...n,...window.chatgenSettings},!e.loaded){var c=document.createElement("script");c.type="text/javascript",c.async=!0,c.src="https://app.chatgen.ai/cmp/chat-widget/bot.js";var r=document.getElementsByTagName("script")[0];r.parentNode.insertBefore(c,r),c.onload=function(){var e={widget_key:"9l0PLPKc"};e.openChatWidget=!0,/iPhone|iPad|iPod|Android/i.test(navigator.userAgent),ChatGen.init(e)}};

  var a = window.ChatGen || [];
  const params = new URLSearchParams(window.location.search);
  const chatgenSettings = {};
  for (let [key, value] of params) {
    if (["server", "key"].indexOf(key) == -1) {
      chatgenSettings[key] = value;
    }
  }
  if (!window.chatgenSettings) {
    window.chatgenSettings = {};
  }
  window.chatgenSettings = {
    ...chatgenSettings,
    ...window.chatgenSettings,
  };
  if (!a.loaded) {
    var s = document.createElement("script");
    s.type = "text/javascript";
    s.async = true;
    s.src = "https://app.chatgen.ai/cmp/chat-widget/bot.js";
    var fs = document.getElementsByTagName("script")[0];
    fs.parentNode.insertBefore(s, fs);
    s.onload = function () {
      var yourKey = "9l0PLPKc";
      var widgetKey = { widget_key: yourKey };
      if (showSmallChatWindow != "yes") {
        widgetKey["openChatWidget"] = true;
      }
      const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
      if (isMobile) {
        // delete widgetKey["openChatWidget"];
      }
      ChatGen.init(widgetKey);
    };
  }
}

const prv = (e) => {
  document.body.innerHTML =
    "<h1>Access Denied</h1><p>You do not have permission to view this page.</p>";
  document.body.style.textAlign = "center";
  document.body.style.backgroundColor = "#f2f2f2";
  document.body.style.color = "#ff0000";
  document.body.style.fontFamily = "Arial, sans-serif";
  document.body.style.marginTop = "100px";
  document.body.style.pointerEvents = "none";
  document.addEventListener("contextmenu", function (e) {
    e.preventDefault();
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "F12") {
      e.preventDefault();
    }
  });
};

window.addEventListener("DOMContentLoaded", (event) => {
  // Site Link
  var url = window.location.href;
  var params = url.indexOf("?") !== -1 ? url.split("?")[1] : "";

  if (params) {
    var queryParams = params.split("&");
    for (var i = 0; i < queryParams.length; i++) {
      var pair = queryParams[i].split("=");
      if (pair[0] === "sitelink") {
        var sitelink = pair[1];
        window.location.href += "#" + sitelink;
        break;
      }
    }
  }

  // Read More
  if (document.getElementsByClassName("more").length > 0) {
    document.querySelector(".more").addEventListener("click", function () {
      var moreText = document.querySelector(".more-cont");
      var btnText = document.querySelector(".more");

      if (moreText.style.display === "none" || moreText.style.display === "") {
        moreText.style.display = "block";
        btnText.innerHTML = "Read less";
      } else {
        moreText.style.display = "none";
        btnText.innerHTML = "Read more";
      }
    });
  }

  // ScrollSpy
  if (!isThanksPage) {
    new ScrollSpy("#navbarNav", "section", 100);
  }

  // Amenities Slider
  if (!isThanksPage) {
    // Main Banner Slider
    let lpSliderSettings = {
      type: "carousel",
      startAt: 0,
      perView: 1,
      gap: 0,
      autoplay: false,
      hoverpause: false,
    };
    if (lpSliderCount > 1) {
      lpSliderSettings = {
        type: "carousel",
        startAt: 0,
        perView: 1,
        gap: 0,
        autoplay: 3000,
        hoverpause: false,
      };
    }
    if (document.getElementById("mainSlider")) {
      new Glide("#mainSlider", lpSliderSettings).mount();
    }

    // Amenities Slider
    if (document.getElementsByClassName("amenities-slider").length > 0) {
      new Glide(".amenities-slider", {
        type: "carousel",
        startAt: 0,
        perView: 3,
        gap: 50,
        autoplay: 3000,
        hoverpause: true,
        breakpoints: {
          800: {
            perView: 2,
          },
          600: {
            perView: 1,
          },
        },
      }).mount();
    }
    if (document.getElementsByClassName("amenities-slider-icon").length > 0) {
      new Glide(".amenities-slider-icon", {
        type: "carousel",
        startAt: 0,
        perView: 4,
        gap: 30,
        autoplay: 3000,
        hoverpause: true,
        breakpoints: {
          800: {
            perView: 2,
          },
          600: {
            perView: 1,
          },
        },
      }).mount();
    }

    // Price Slider
    let item3n = {
      type: "carousel",
      startAt: 0,
      perView: 3,
      gap: 20,
      autoplay: 3000,
      hoverpause: true,
      breakpoints: {
        1125: {
          perView: 2,
        },
        650: {
          perView: 1,
        },
      },
    };
    let item3 = {
      type: "carousel",
      startAt: 0,
      perView: 3,
      gap: 20,
      autoplay: false,
      breakpoints: {
        1125: {
          autoplay: 3000,
          hoverpause: true,
          perView: 2,
        },
        650: {
          autoplay: 3000,
          hoverpause: true,
          perView: 1,
        },
      },
    };
    let item2 = {
      type: "slider",
      startAt: 0,
      perView: 3,
      gap: 20,
      autoplay: false,
      arrows: false,
      breakpoints: {
        1125: {
          autoplay: false,
          perView: 2,
        },
        650: {
          autoplay: 3000,
          hoverpause: true,
          perView: 1,
        },
      },
    };
    let item1 = {
      type: "slider",
      startAt: 0,
      perView: 3,
      gap: 20,
      autoplay: false,
      focusAt: "center",
      breakpoints: {
        1125: {
          autoplay: false,
          perView: 2,
        },
        650: {
          autoplay: false,
          perView: 1,
        },
      },
    };
    const pillsPriceButton = document.getElementById("pills-price");
    if (pillsPriceButton) {
      const tabLinks = document.querySelectorAll("#pills-price button");
      tabLinks.forEach((tab) => {
        tab.addEventListener("shown.bs.tab", function (event) {
          const targetTab = event.target.getAttribute("data-bs-target");
          if (!targetTab) return;

          let slideitems = tab.getAttribute("data-slideitems");

          let tsPriceSliderOptions;
          if (slideitems == 1) {
            tsPriceSliderOptions = item1;
          } else if (slideitems == 2) {
            tsPriceSliderOptions = item2;
          } else if (slideitems == 3) {
            tsPriceSliderOptions = item3;
          } else {
            tsPriceSliderOptions = item3n;
          }

          const glideElement = document.querySelector(targetTab + " .glide");
          if (glideElement && !glideElement.classList.contains("initialized")) {
            new Glide(glideElement, tsPriceSliderOptions).mount();
            glideElement.classList.add("initialized");
          }
        });
      });
      const firstTab = document.querySelector("#pills-price button.active");
      const firstTabContent = document.querySelector(
        firstTab.getAttribute("data-bs-target") + " .glide"
      );
      if (
        firstTabContent &&
        !firstTabContent.classList.contains("initialized")
      ) {
        let slideitems = firstTab.getAttribute("data-slideitems");

        let initPriceSliderOptions;
        if (slideitems == 1) {
          initPriceSliderOptions = item1;
        } else if (slideitems == 2) {
          initPriceSliderOptions = item2;
        } else if (slideitems == 3) {
          initPriceSliderOptions = item3;
        } else {
          initPriceSliderOptions = item3n;
        }

        new Glide(firstTabContent, initPriceSliderOptions).mount();
        firstTabContent.classList.add("initialized");
      }
    } else {
      if (document.querySelector("#price-slider")) {
        const priceSTable = document.querySelector("#price-slider");
        let slideitems = priceSTable.getAttribute("data-slideitems");
        let initPriceSliderOptions;
        if (slideitems == 1) {
          initPriceSliderOptions = item1;
        } else if (slideitems == 2) {
          initPriceSliderOptions = item2;
        } else if (slideitems == 3) {
          initPriceSliderOptions = item3;
        } else {
          initPriceSliderOptions = item3n;
        }
        new Glide(priceSTable, initPriceSliderOptions).mount();
      }
    }

    // Gallery Slider
    let galSliderOptions = {
      type: "carousel",
      startAt: 0,
      perView: 3,
      gap: 50,
      autoplay: 3000,
      hoverpause: true,
      breakpoints: {
        800: {
          perView: 2,
        },
        600: {
          perView: 1,
        },
      },
    };
    const pillsGalButton = document.getElementById("galTab-tab");
    if (pillsGalButton) {
      const tabGalLinks = document.querySelectorAll("#galTab-tab button");
      tabGalLinks.forEach((tab) => {
        tab.addEventListener("shown.bs.tab", function (event) {
          const targetTab = event.target.getAttribute("data-bs-target");
          if (!targetTab) return;

          const glideGalElement = document.querySelector(targetTab + " .glide");
          if (
            glideGalElement &&
            !glideGalElement.classList.contains("initialized")
          ) {
            new Glide(glideGalElement, galSliderOptions).mount();
            glideGalElement.classList.add("initialized");
          }
        });
      });
      const firstGalTab = document.querySelector("#galTab-tab button.active");
      const firstGalTabContent = document.querySelector(
        firstGalTab.getAttribute("data-bs-target") + " .glide"
      );
      if (
        firstGalTabContent &&
        !firstGalTabContent.classList.contains("initialized")
      ) {
        new Glide(firstGalTabContent, galSliderOptions).mount();
        firstGalTabContent.classList.add("initialized");
      }
    } else {
      if (document.querySelector("#price-slider")) {
        const GalSTable = document.querySelector("#price-slider");
        new Glide(GalSTable, galSliderOptions).mount();
      }
    }
  }

  // Lightbox
  const lightbox = GLightbox({
    selector: ".glightbox",
    loop: true,
  });

  // Auto Collapse Mobile Navbar
  if (!isThanksPage) {
    const navLinks = document.querySelectorAll(".navbar-nav .nav-link");
    navLinks.forEach((link) => {
      link.addEventListener("click", () => {
        setTimeout(() => {
          const navbarCollapse = document.getElementById("navbarNav");
          const bootstrapCollapse = new bootstrap.Collapse(navbarCollapse, {
            toggle: false,
          });
          bootstrapCollapse.hide();
        }, 1000);
      });
    });
  }

  // Enquiry Popup
  const enqPopupHighNormal = document.querySelector(
    "#enq-popup-pj-highlights-normal"
  );
  const enqPopupHighBrochure = document.querySelector(
    "#enq-popup-pj-highlights-brochure"
  );
  const enqPopups = document.querySelectorAll('[data-bs-target="#enqPopup"]');
  var formName = document.querySelector("#enqPopup #form-name");
  const enqPopupTitle = document.querySelector("#enqPopup #enqPopupTitle");
  const enqPopupSumit = document.querySelector("#enqPopup #enqPopupSumit");
  const enqPopupForm = document.querySelector("#enqPopup .enq-form");
  enqPopups.forEach((enqPopup) => {
    enqPopup.addEventListener("click", function (event) {
      var formNameValue = this.getAttribute("data-form-name");
      if (formNameValue && formNameValue.trim() !== "") {
        formName.value = formNameValue;
      } else {
        formName.value = "Enquiry Now";
      }

      if (this.getAttribute("data-brochure-popup") === "yes") {
        enqPopupHighNormal.classList.add("d-none");
        enqPopupHighBrochure.classList.remove("d-none");
      } else {
        enqPopupHighBrochure.classList.add("d-none");
        enqPopupHighNormal.classList.remove("d-none");
      }

      if (enqPopup.dataset.formtitle) {
        enqPopupTitle.innerHTML = enqPopup.dataset.formtitle;
        enqPopupSumit.innerHTML = enqPopup.dataset.formbtn;
      } else {
        enqPopupTitle.textContent = "Enquiry Now";
        enqPopupSumit.textContent = "Submit";
      }

      const enqNameValue = this.getAttribute("data-form-enq-name");
      let existingInput = enqPopupForm.querySelector(
        'input[name="form-enq-name"]'
      );

      if (enqNameValue) {
        if (!existingInput) {
          existingInput = document.createElement("input");
          existingInput.type = "hidden";
          existingInput.name = "form-enq-name";
          enqPopupForm.appendChild(existingInput);
        }
        existingInput.value = enqNameValue;
      } else if (existingInput) {
        existingInput.remove();
      }
    });
  });
});

window.addEventListener("load", (event) => {
  // Lazyload
  lazySizes.init();

  const loader = new ResourceLoader();

  // Assets Load
  setTimeout(() => {
    // Favicon
    loadFavicon("pp");

    // Intl Tel Input
    loader
      .loadCSS(
        "https://cdn.jsdelivr.net/npm/intl-tel-input@24.7.0/build/css/intlTelInput.min.css"
      )
      .then((message) => console.log())
      .catch((error) => console.error(error));
    loader
      .loadJS(
        "https://cdn.jsdelivr.net/npm/intl-tel-input@24.7.0/build/js/intlTelInput.min.js"
      )
      .then((message) => loadTelInput(countryCode))
      .catch((error) => console.error(error));

    // Google Font
    loader
      .loadCSS(
        "https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
      )
      .then((message) => loadBaseFont())
      .catch((error) => console.error(error));

    // Remix Icon Font
    loader
      .loadCSS(
        "https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.min.css"
      )
      .then((message) => console.log())
      .catch((error) => console.error(error));

    // GTranslate
    window.gtranslateSettings = {
      default_language: "en",
      languages: ["en", "ar"],
      wrapper_selector: ".gtranslate_wrapper",
      flag_size: 24,
      alt_flags: { en: "usa" },
    };
    loader
      .loadJS("https://cdn.gtranslate.net/widgets/latest/popup.js")
      .then((message) => console.log())
      .catch((error) => console.error(error));

    setTimeout(() => {
      if (window.gtranslate && window.gtranslate.setLang) {
        window.gtranslate.setLang("ar"); // Switch language to Arabic
      }
    }, 1000);

    // Auto Enquire Popup
    // var enqPopup = new bootstrap.Modal(document.getElementById('enqPopup'));
    // enqPopup.show();
  }, 3000);

  // GTM
  if (codeGTM && !isThanksPage) {
    setTimeout(() => {
      (function (w, d, s, l, i) {
        w[l] = w[l] || [];
        w[l].push({ "gtm.start": new Date().getTime(), event: "gtm.js" });
        var f = d.getElementsByTagName(s)[0],
          j = d.createElement(s),
          dl = l != "dataLayer" ? "&l=" + l : "";
        j.async = true;
        j.src = "https://www.googletagmanager.com/gtm.js?id=" + i + dl;
        f.parentNode.insertBefore(j, f);
      })(window, document, "script", "dataLayer", "GTM-" + codeGTM);
    }, 3000);
  }

  // Active Form Sumbit Buttons
  setTimeout(() => {
    const forms = document.querySelectorAll("form");
    forms.forEach((form) => {
      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.removeAttribute("disabled");
      }
    });
  }, 3000);

  // Chatbot
  if (isChatbot != "no") {
    let proName = atob(cbpron);
    let proNameComp = atob(cbproncomp);
    window.chatgenSettings = { enquiry: proName, description: proNameComp };
    setTimeout(() => {
      loadChatgen(showSmallChatWindow);
      setTimeout(() => {
        const chatElem = document.getElementById("selekt-chat-widget");
        chatElem.classList.add("chatbot-fix-bottom");

        /* if(window.innerWidth < 800){
                    chatElem.classList.add('chatbot-fix');
                }else{
                    chatElem.classList.add('chatbot-fix-bottom');
                }

                const navChatCheck = document.querySelector('.navbar-collapse');
                document.querySelector('.navbar-toggler').addEventListener('click', function() {
                    if (window.scrollY == 0) {
                        setTimeout( () => {
                            if (navChatCheck.classList.contains('show')) {
                                chatElem.classList.remove('chatbot-fix');
                                chatElem.classList.add('chatbot-fix-bottom');
                            } else {
                                chatElem.classList.remove('chatbot-fix-bottom');
                                chatElem.classList.add('chatbot-fix');
                            }
                        }, 500);
                    }
                });

                if(window.innerWidth < 800){
                    window.addEventListener('scroll', function() {
                        
                        if (window.scrollY > 0) {
                            chatElem.classList.remove('chatbot-fix');
                            chatElem.classList.add('chatbot-fix-bottom');
                        } else {
                            chatElem.classList.remove('chatbot-fix-bottom');
                            chatElem.classList.add('chatbot-fix');
                        }

                    });
                } */
      }, 2000);
    }, CUSTOM_DELAY);
  }
});