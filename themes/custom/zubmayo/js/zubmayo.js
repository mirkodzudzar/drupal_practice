/**
 * @file
 * Add javascript here.
 */
(function ($, Drupal) {
  Drupal.behaviors.zubmayo = {
    attach: function (context, settings) {
      var length_ing = $(".field--name-field-ingredien .field__items > div").length;
      var length_pre = $(".field--name-field-steps-of-preparation .field__items > div").length;
      var i;
      var j;

      for(i = 1; i <= length_ing; i++) {
        $('#ingredient-' + i, context).click(function() {
          $(this).toggleClass('checked');
        });
      }

      for(j = 1; j <= length_pre; j++) {
        $('#preparation-' + j, context).click(function() {
          $(this).toggleClass('checked');
        });
      }
    }
  };
})(jQuery, Drupal);