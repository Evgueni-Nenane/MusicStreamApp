$(function () {
    $('[data-modal-target]').on('click', function (event) {
        event.preventDefault();

        var target = document.querySelector($(this).attr('data-modal-target'));

        if (target) {
            bootstrap.Modal.getOrCreateInstance(target).show();
        }
    });

    $('[data-modal-close]').on('click', function (event) {
        event.preventDefault();

        var modal = $(this).closest('.modal')[0];

        if (modal) {
            bootstrap.Modal.getOrCreateInstance(modal).hide();
        }
    });

    $('[data-auto-modal]').each(function () {
        var selector = $(this).attr('data-auto-modal');
        var element = document.querySelector(selector);

        if (!element) {
            return;
        }

        var after = $(this).attr('data-auto-modal-after');

        if (after) {
            $(element).one('shown.bs.modal', function () {
                var next = document.querySelector(after);

                if (next) {
                    bootstrap.Modal.getOrCreateInstance(next).show();
                }
            });
        }

        bootstrap.Modal.getOrCreateInstance(element).show();
    });
});
