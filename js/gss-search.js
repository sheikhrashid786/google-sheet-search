jQuery(document).ready(function ($) {
  $("#gss-search-input").on("input", function () {
    var search_value = $(this).val();
    $("#gss-search-results").empty();
    if (search_value.length >= 1) {
      // Optionally, only start searching after a certain number of characters
      $('.bot-container').show();
      $.ajax({
        url: gss_ajax.ajax_url,
        type: "POST",
        data: {
          action: "gss_search",
          search_value: search_value,
        },
        success: function (response) {
          $('.bot-container').hide();
          $("#gss-search-results").html(response);
        },
      });
    } else {
      $("#gss-search-results").empty();
    }
  });
});
