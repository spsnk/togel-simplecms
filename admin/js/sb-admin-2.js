(function ($) {
  "use strict"; // Start of use strict
  // Toggle the side navigation
  $("#sidebarToggle, #sidebarToggleTop").on("click", function (e) {
    $("body").toggleClass("sidebar-toggled");
    $(".sidebar").toggleClass("toggled");
    if ($(".sidebar").hasClass("toggled")) {
      $(".sidebar .collapse").collapse("hide");
    }
  });

  // Close any open menu accordions when window is resized below 768px
  $(window).resize(function () {
    if ($(window).width() < 768) {
      $(".sidebar .collapse").collapse("hide");
    }

    // Toggle the side navigation when window is resized below 480px
    if ($(window).width() < 480 && !$(".sidebar").hasClass("toggled")) {
      $("body").addClass("sidebar-toggled");
      $(".sidebar").addClass("toggled");
      $(".sidebar .collapse").collapse("hide");
    }
  });

  // Prevent the content wrapper from scrolling when the fixed side navigation hovered over
  $("body.fixed-nav .sidebar").on(
    "mousewheel DOMMouseScroll wheel",
    function (e) {
      if ($(window).width() > 768) {
        var e0 = e.originalEvent,
          delta = e0.wheelDelta || -e0.detail;
        this.scrollTop += (delta < 0 ? 1 : -1) * 30;
        e.preventDefault();
      }
    }
  );

  // Scroll to top button appear
  $(document).on("scroll", function () {
    var scrollDistance = $(this).scrollTop();
    if (scrollDistance > 100) {
      $(".scroll-to-top").fadeIn();
    } else {
      $(".scroll-to-top").fadeOut();
    }
  });

  // Smooth scrolling using jQuery easing
  $(document).on("click", "a.scroll-to-top", function (e) {
    var $anchor = $(this);
    $("html, body")
      .stop()
      .animate(
        {
          scrollTop: $($anchor.attr("href")).offset().top,
        },
        1000,
        "easeInOutExpo"
      );
    e.preventDefault();
  });

  $.fn.inputFilter = function (inputFilter) {
    return this.on(
      "input keydown keyup mousedown mouseup select contextmenu drop",
      function () {
        if (inputFilter(this.value)) {
          this.oldValue = this.value;
          this.oldSelectionStart = this.selectionStart;
          this.oldSelectionEnd = this.selectionEnd;
        } else if (this.hasOwnProperty("oldValue")) {
          this.value = this.oldValue;
          this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
        } else {
          this.value = "";
        }
      }
    );
  };

  $.fn.serializeObject = function () {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function () {
      if (o[this.name]) {
        if (!o[this.name].push) {
          o[this.name] = [o[this.name]];
        }
        o[this.name].push(this.value || "");
      } else {
        o[this.name] = this.value || "";
      }
    });
    return o;
  };

  let live_draw_timer = null;
  const API_URL = "../api";
  const load_data = (cache = true) => {
    let cache_string = "";
    if (cache) {
      cache_string = `?_=${Math.floor(Math.random() * 999999) + 1}`;
    }
    axios.get(API_URL + "/livedraw" + cache_string).then((response) => {
      console.log("LiveDraw response ->", response);
      let date = new Date(response.data.data.next);
      let countDownDate = date.getTime();
      $("#local-time-live-draw").text(date.toLocaleString());
      const setTime = () => {
        // Get today's date and time
        var now = new Date().getTime();

        // Find the distance between now and the count down date
        var distance = countDownDate - now;

        // Time calculations for days, hours, minutes and seconds
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor(
          (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
        );
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        // Display the result in the element with id="demo"
        $("#timer").html(
          days + "d " + hours + "h " + minutes + "m " + seconds + "s "
        );

        // If the count down is finished, write some text
        if (distance < 0) {
          clearInterval(live_draw_timer);
          $("#timer").html("EXPIRED");
          load_data(true);
        }
      };
      live_draw_timer = setInterval(setTime, 1000);
      setTime();
    });
    clearInterval(live_draw_timer);
    axios.get(API_URL + "/latest" + cache_string).then((response) => {
      console.log("Latest response ->", response);
      $("#latest").html(
        response.data.data.first
          .split("")
          .map((c) => `<span class="number">${c}</span>`)
      );
      $("#latest-date").html(response.data.data.day);
    });
    axios
      .get(API_URL + "/config" + cache_string, {
        headers: { "X-TOGEL-API-KEY": "71f3fb91-9bb4-4711-be40-b76ba9ba5cd1" },
      })
      .then((response) => {
        $("#timepicker").val(
          response.data.data.hour.padStart(2, "0") +
            ":" +
            response.data.data.minutes.padStart(2, "0")
        );
      });
    table.ajax.reload();
    upcoming_table.ajax.reload();
  };
  var table = $("#latest-results").DataTable({
    ajax: { url: API_URL + "/results/pages/1" },
    columns: [
      {
        data: "day",
        type: "date",
        render: (data) =>
          `<a data-toggle="tooltip" data-placement="right" title="Local time: ${new Date(
            data
          ).toDateString()}">${data}</a>`,
      },
      { data: "first", render: (data) => `<strong>${data}</strong>` },
      { data: "second", render: (data) => `<small>${data}</small>` },
      { data: "third", render: (data) => `<small>${data}</small>` },
    ],
    searching: false,
    paging: false,
    info: false,
    order: [[0, "desc"]],
  });
  var upcoming_table = $("#upcoming-results").DataTable({
    ajax: {
      url: API_URL + "/results/upcoming",
      headers: { "X-TOGEL-API-KEY": "71f3fb91-9bb4-4711-be40-b76ba9ba5cd1" },
    },
    columns: [
      {
        data: "day",
        type: "date",
        render: (data) =>
          `<a data-toggle="tooltip" data-placement="right" title="Local time: ${new Date(
            data
          ).toDateString()}">${data}</a>`,
      },
      { data: "first", render: (data) => `<strong>${data}</strong>` },
      { data: "second", render: (data) => `<small>${data}</small>` },
      { data: "third", render: (data) => `<small>${data}</small>` },
    ],
    searching: false,
    paging: false,
    info: false,
    order: [[0, "asc"]],
  });
  $("input[name=day]").datepicker({
    showButtonPanel: false,
    dateFormat: "yy-mm-dd",
  });
  $("#day").datepicker("setDate", "today");
  $(".number-only").inputFilter(function (value) {
    return /^\d*$/.test(value); // Allow digits only, using a RegExp
  });
  function fill_random(selector) {
    const digits = 6;
    $(`input[name='${selector}']`).each((i, e) => {
      let n = String(
        Math.floor(Math.random() * (Math.pow(10, digits) + 1))
      ).padStart(digits, 0);
      $(e).val(n);
    });
  }
  $(`button[data-target]`).on("click", function () {
    fill_random($(this).data("target"));
  });
  $("#new-entry-form").on("submit", function (e) {
    e.preventDefault();
    e.stopPropagation();
    let form = $(e.currentTarget);
    let data = form.serializeObject();
    $("#form-loading").show();
    $("#form-success").hide();
    $("#form-feedback").hide();
    axios
      .post(API_URL + "/results", data, {
        headers: { "X-TOGEL-API-KEY": "71f3fb91-9bb4-4711-be40-b76ba9ba5cd1" },
      })
      .then((response) => {
        load_data(true);
        $("#form-success")
          .html(
            `<em class="fas fa-check fa-fw"></em>Created entry for ${response.data.data.day}`
          )
          .show();
        $("#form-loading").hide();
        $("#new-entry-form").trigger("reset");
        fill_random("consolation");
        fill_random("starter");
      })
      .catch((error) => {
        if (error.response) {
          $("#form-loading").hide();
          const status = error.response.status;
          if (status == 409) {
            $("#form-feedback")
              .html("Results for this date already exist!")
              .show();
          } else if (status >= 400 && status < 500) {
            $("#form-feedback").html("Incomplete data.").show();
          } else {
            $("#form-feedback").html("Server error, contact developer.").show();
          }
        } else {
          $("#form-feedback")
            .html(`Error: [${error.message}]. Contact developer.`)
            .show();
        }
      })
      .finally(() => {
        var x = setInterval(() => {
          $("#form-feedback").hide();
          $("#form-loading").hide();
          clearInterval(x);
        }, 5000);
      });
  });
  $("#delete-entry-form").on("submit", function (e) {
    e.preventDefault();
    e.stopPropagation();
    let form = $(e.currentTarget);
    let data = form.serializeObject();
    axios.delete(API_URL + `/results/${data.day}`, {
      headers: { "X-TOGEL-API-KEY": "71f3fb91-9bb4-4711-be40-b76ba9ba5cd1" },
    });
    load_data(true);
    form.trigger("reset");
  });
  $("#change-config-form").on("submit", function (e) {
    e.preventDefault();
    e.stopPropagation();
    let form = $(e.currentTarget);
    let data = form.serializeObject();
    let time = data.time.split(":").map((t) => parseInt(t));
    let time_object = {
      hour: time[0],
      minutes: time[1],
    };
    axios
      .put(API_URL + "/config", time_object, {
        headers: { "X-TOGEL-API-KEY": "71f3fb91-9bb4-4711-be40-b76ba9ba5cd1" },
      })
      .then((response) => load_data(true));
  });
  fill_random("consolation");
  fill_random("starter");
  $(function () {
    $('[data-toggle="tooltip"]').tooltip();
  });
  $("#timepicker").timepicker({
    timeFormat: "H:i",
  });
  load_data(true);
})(jQuery); // End of use strict
