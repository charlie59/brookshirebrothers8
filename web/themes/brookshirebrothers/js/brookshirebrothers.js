(function ($, Drupal) {
  "use strict";
  Drupal.behaviors.brookshireBrothers = {
    attach: function (context, settings) {
      $("#superfish-main-toggle").once("brookshireBrothers").append('<i class="fas fa-bars"></i>');
      $(window).resize(function () {
        $("#superfish-main-toggle").once("brookshireBrothers").append('<i class="fas fa-bars"></i>');
      });
    }
  };
})(jQuery, Drupal);
