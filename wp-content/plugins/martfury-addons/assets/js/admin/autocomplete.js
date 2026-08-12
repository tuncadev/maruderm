(function ($) {
    'use strict';

    var ControlFMautocomplete = elementor.modules.controls.BaseData.extend({
        onReady: function () {

            this.mf_Autocomplete(this);

            this.mf_RemoveData(this);

            this.mf_Sortable(this);

            this.mf_OnRender(this);
        },
        mf_Autocomplete: function (self) {
            var $input_value = self.$el.find('.mf_autocomplete_value'),
                self_value = $input_value.val(),
                multiple = $input_value.data('multiple'),
                step = '',
                item_value = '';

            self.$el.find('.mf_autocomplete_param').autocomplete({
                minLength: 1,
                source: function (request, response) {
                    $.ajax({
                        url: ajaxurl,
                        dataType: 'json',
                        method: 'post',
                        data: {
                            action: 'mf_get_autocomplete_suggest',
                            term: request.term,
                            source: $input_value.data('source')
                        },
                        success: function (data) {
                            response(data.data);
                        }
                    })
                },
                response: function (event, ui) {
                    self.$el.find('.mf_autocomplete').removeClass('loading');
                },
                search: function (event, ui) {
                    self.$el.find('.mf_autocomplete').addClass('loading');
                },
                select: function (event, ui) {

                    item_value = ui.item.value;

                    if (item_value === 'nothing-found') {
                        return false;
                    }
                    self_value = $input_value.val();
                    if (self_value !== '') {
                        step = ',';
                    }

                    var template = '<li class="mf_autocomplete-label" data-value="' + item_value + '">' +
                        '<span class="mf_autocomplete-data">' + ui.item.label + '</span>' +
                        '<a href="#" class="mf_autocomplete-remove">×</a>' +
                        '</li>';

                    if (multiple) {
                        self.$el.find('.mf_autocomplete').append(template);
                        self_value = self_value + step + item_value;
                    } else {
                        if( self.$el.find('.mf_autocomplete .mf_autocomplete-label').length > 0 ) {
                            self.$el.find('.mf_autocomplete .mf_autocomplete-label').replaceWith(template);
                        } else {
                            self.$el.find('.mf_autocomplete').append(template);
                        }
                        self.$el.find('.mf_autocomplete .mf_autocomplete-label').replaceWith(template);
                        self_value = item_value;
                    }

                    self.$el.find('.mf_autocomplete_param').val('');
                    $input_value.val(self_value);
                    self.setValue(self_value);

                    return false;
                },
                open: function (event) {
                    $(event.target).data('uiAutocomplete').menu.activeMenu.addClass('elementor-autocomplete-menu mf-autocomplete-menu');
                }
            }).autocomplete('instance')._renderItem = function (ul, item) {
                return $('<li>')
                    .attr('data-value', item.value)
                    .append(item.label)
                    .appendTo(ul);
            };
            return self_value;
        },
        mf_RemoveData: function (self) {
            var $input_value = self.$el.find( '.mf_autocomplete_value' );
			self.$el.find( '.mf_autocomplete' ).on( 'click', '.mf_autocomplete-remove', function ( e ) {
				e.preventDefault();
				var $this = $( this ),
					self_value = '';

				$this.closest( '.mf_autocomplete-label' ).remove();

				self.$el.find( '.mf_autocomplete' ).find( '.mf_autocomplete-label' ).each( function () {
					self_value = self_value + ',' + $( this ).data( 'value' );
				} );
				$input_value.val(self_value);
                self.setValue( self_value );

            } );


        },
        mf_Sortable: function (self) {
            var sortable = self.$el.find('.mf_autocomplete_value').data('sortable'),
                self_value = '';
            if (sortable) {
                self.$el.find('.mf_autocomplete').sortable({
                    items: 'li.mf_autocomplete-label',
                    update: function (event, ui) {

                        self_value = '';

                        self.$el.find('.mf_autocomplete').find('li.mf_autocomplete-label').each(function () {
                            self_value = self_value + ',' + $(this).data('value');
                        });

                        self.setValue(self_value);
                    }
                });
            }
        },
        mf_OnRender: function (self) {
            var $input_value = self.$el.find('.mf_autocomplete_value'),
                self_value = $input_value.val();

            $.ajax({
                url: ajaxurl,
                dataType: 'json',
                method: 'post',
                data: {
                    action: 'mf_get_autocomplete_render',
                    term: self_value,
                    source: $input_value.data('source')
                },
                success: function (data) {
                    if (data) {
                        self.$el.find('.mf_autocomplete').append(data.data);
                        self.$el.find('.mf_autocomplete').find('li.mf_autocomplete-loading').remove();
                    }
                }
            });
        },
        onBeforeDestroy: function () {
            if (this.ui.input.data('autocomplete')) {
                this.ui.input.autocomplete('destroy');
            }

            this.$el.remove();
        }
    });
    elementor.addControlView('mf_autocomplete', ControlFMautocomplete);

})
(jQuery);