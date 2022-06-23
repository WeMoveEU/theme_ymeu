(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.counter = {
    attach: function () {
      $(".counter").each(function () {
        var number = strToFloat($(this).find('.number').text()),
          upperLimit = strToFloat($(this).find('.progress-bar-limits .limit-upper').text());

        $(this).find('.progress-bar .progress-bar-slider').css('width', parseInt((number / upperLimit) * 100) + '%');

        function strToFloat(str) {
          return parseFloat(str.trim().replace(',', '').replace(' ', ''));
        }
      });
    }
  }

  Drupal.behaviors.webform__tooltip = {
    attach: function () {
      var tooltipSelector = '.webform-component-markup[class*="-note"]',
        tooltipActiveClass = 'tooltip-active';
      $(tooltipSelector).eq(0).addClass(tooltipActiveClass);

      // For HTML text area control
      $('form.webform-client-form').click(function (e) {
        var webformItem = $(e.target).closest('.text-format-wrapper, .webform-component.form-item');

        $(tooltipSelector).removeClass(tooltipActiveClass);
        webformItem.next(tooltipSelector).addClass(tooltipActiveClass);
      });
    }
  }

  Drupal.behaviors.webform__file__popover = {
    attach: function () {
      $(document).ready(function () {
        var popoverTriggerList = [].slice.call(document.querySelectorAll('.webform-component-file [data-bs-toggle="popover"]'))
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
          return new bootstrap.Popover(popoverTriggerEl, {
            content: function () {
              return $(this).next('#upload-instructions').html();
            }
          })
        });
      })
    }
  }

  Drupal.behaviors.webform__validation_ui = {
    attach: function () {
      $.validator.messages.required = Drupal.t('Fill that field.');

      $("form.webform-client-form").validate({
        highlight: function (element, errorClass, validClass) {
          $(element).addClass(errorClass).removeClass(validClass);
          $(element).closest(".form-item").addClass(errorClass);
        },
        unhighlight: function (element, errorClass, validClass) {
          $(element).removeClass(errorClass).addClass(validClass);
          $(element).closest(".form-item").removeClass(errorClass);
        },
        errorElement: "div"
      });
    }
  };

  Drupal.behaviors.webform__form_partners_logos = {
    attach: function () {
      $('.multi-logos input[type="radio"]').on('change', function () {
        $(this).parents('.webform-component-radios').addClass('radio-checked');
      });

      $('.multi-logos input[type="radio"]:checked').each(function () {
        $(this).parents('.webform-component-radios').addClass('radio-checked');
      });


      $('.page-node-submission:not(.page-node-submission-edit) .webform-submission .webform-component--partners .panel-body div[class*="--partner"] a').each(function () {
        var src = $(this).attr('href');
        $('<img src="' + src + '" />').insertBefore($(this));
        $(this).addClass('sr-only');
      });
    }
  };

  Drupal.behaviors.webform__campaign = {
    attach: function () {
      var campaignId = Drupal.settings.webformCampaignId,
        campaignPage = $(`.page-node-${campaignId}`),
        campaignNode = $(`#webform-client-form-${campaignId}`);

      // Massages after progressbar
      $('.messages', campaignPage).insertAfter($('.webform-progressbar', campaignNode));

      // Field Language - discription under label
      $('.webform-component--other-data--language', campaignNode).find('.description').detach().insertAfter($('.select-or-other .form-type-radios > label', campaignNode));

      // Field Summary - under action buttons
      $('.webform-component--summary-submission-not-published, .webform-component--summary-submission-published', campaignNode).insertAfter($('.form-actions', campaignNode));

      // Add placeholder to Password fields (based on labels)
      $('.webform-component-password', campaignNode).each(function () {
        var labelTxt = $(this).find('label').text();
        $(this).find('input').attr('placeholder', labelTxt);
      });
    }
  }

  Drupal.behaviors.webform__campaign__submit_on_enter = {
    attach: function () {
      var campaignId = Drupal.settings.webformCampaignId,
        parent = $(`#webform-client-form-${campaignId} .webform-component--user-form`),
        indicator = $('input#edit-submitted-user-form-login-form-login,'
          + 'input#edit-submitted-user-form-login-form-login-password,'
          + 'input#edit-submitted-user-form-civicrm-1-contact-1-fieldset-fieldset-repassword', parent);

      indicator.each(function () {
        $(this).on("keypress", function (e) {
          if (e.keyCode === 13) {
            e.preventDefault();
            $(`.webform-client-form-${campaignId} .btn.webform-submit.button-primary`).click();
          }
        });
      });
    }
  };

  Drupal.behaviors.user_profile__password_indicator = {
    attach: function (context) {
      var passIndicator = $('#edit-account .form-item-pass-pass1 .progress'),
        passCtrl = $('#edit-account .form-item-pass-pass1 input[type="password"]');
      passIndicator.hide();

      passCtrl.on('input', function () {
        if (this.value != '') { passIndicator.show(); }
        else { passIndicator.hide(); }
      });
    }
  };



})(jQuery, Drupal);  