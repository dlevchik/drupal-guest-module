(function ($) {
  $(document).ready(function () {
    let guests_actions = $(".guest-buttons-drop-out");
    guests_actions.each(item => {
      let container = guests_actions[item];
      let button = $(container).children(".guest-actions-toggle");
      let tooltip = $(container).children(".guest-actions");
      // let popper = Popper.createPopper($(button), $(tooltip), {
      //   placement: 'left',
      // });
      // popper.update();
      // @todo: fix popper;
      $(tooltip).css("left", - $(tooltip).outerWidth() / 2);
      $(tooltip).hide();
    });
    $(document.body).on("click", (event) => {
      let guest_toggle = event.target.closest(".guest-actions-toggle");
      if (!guest_toggle) {return;}
      event.preventDefault();

      let guest_container = guest_toggle.closest(".guest-buttons-drop-out");
      let buttons = $(guest_container).children(".guest-actions");
      $(buttons).toggle();
    });
  });
})(jQuery);
