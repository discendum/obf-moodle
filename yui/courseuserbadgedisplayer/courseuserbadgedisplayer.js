YUI.add('moodle-local_obf-courseuserbadgedisplayer', function(Y) {
    var DISPLAYERNAME = 'obf-courseuserbadgedisplayer';
    var COURSEUSERBADGEDISPLAYER = function() {
        COURSEUSERBADGEDISPLAYER.superclass.constructor.apply(this, arguments);
    };

    Y.extend(COURSEUSERBADGEDISPLAYER, Y.Base, {
        /**
         * Module configuration
         */
        config: null,
        /**
         * Panel that displays a single assertion
         */
        panel: null,
        /**
         * Assertion cache
         */
        assertions: {},
        /**
         * Precompiled templates
         */
        templates: {},
        /**
         * If true, we're dealing only with a single list of badges (one user, one backpack).
         */
        init_list_only: false,
        /**
         * Module initializer
         *
         * @param Object config
         */
        initializer: function(config) {
            this.config = config;
            this.assertions = config.assertions || {};
            this.init_list_only = config.init_list_only || false;

            // compile templates
            this.templates.assertion = this.compile_template(unescape(this.config.tpl.assertion));
            this.templates.badge = this.compile_template(unescape(this.config.tpl.badge));
            this.templates.list = this.compile_template(unescape(this.config.tpl.list));

            // do it!
            if (this.init_list_only) {
                this.process_single();
            }
            else {
                this.process();
            }
        },
        init_panel: function() {
            this.panel = new Y.Panel({
                id: 'obf-assertion-panel',
                headerContent: '',
                centered: true,
                modal: true,
                visible: false,
                width: 600,
                render: true,
                zIndex: 10,
                buttons: [
                    {
                        value: M.util.get_string('closepopup', 'local_obf'),
                        action: function (e) { e.preventDefault(); this.hide(); },
                        section: Y.WidgetStdMod.FOOTER
                    }
                ]
            });
        },
        process_single: function() {
            this.init_panel();
            Y.one('ul.badgelist').delegate('click', this.display_badge, 'li', this);
        },
        process: function() {
            var table = Y.one('table#obf-participants');

            if (!table) {
                return;
            }

            this.init_panel();

            // Show badges of a participants
            table.delegate('click', function(e) {
                e.preventDefault();

                var node = e.currentTarget;
                var parentrow = node.ancestor('tr');

                this.toggle_badge_row(parentrow);
            }, 'td.show-badges a', this);

            // Display a single badge
            table.delegate('click', this.display_badge, 'ul.badgelist li', this);
        },
        display_badge: function(e) {
            e.preventDefault();

            var node = e.currentTarget;
            var data = this.assertions[node.generateID()];

            this.panel.set('bodyContent', this.templates.assertion(data));
            this.panel.set('headerContent', data.badge.name);
            this.panel.show();
        },
        toggle_badge_row: function(row) {
            var badgerow = row.next();

            if (!badgerow || !badgerow.hasClass('badge-row')) {
                var target = row.one('td.show-badges');
                var spinner = !!M.util.add_spinner ? M.util.add_spinner(Y, target) : false;
                var cellcount = row.all('td').size();
                var badgecell = Y.Node.create('<td></td>').setAttribute('colspan', cellcount);
                var userid = row.generateID().split('-')[1];

                badgerow = Y.Node.create('<tr></tr>').append(badgecell).addClass('badge-row').
                        setStyle('display', 'none');
                row.insert(badgerow, 'after');

                if (spinner !== false) {
                spinner.show();
                }

                this.insert_badges(userid, badgecell, function() {
                    badgerow.toggleView();

                    if (spinner !== false) {
                    spinner.hide();
                    }
                });

            }
            else {
                badgerow.toggleView();
            }

        },
        insert_badges: function(userid, cell, callback) {
            Y.io(this.config.url, {
                data: {userid: userid},
                on: {complete: Y.bind(this.receive_badges, this)},
                arguments: {cell: cell, callback: callback, userid: userid}
            });
        },
        receive_badges: function(transactionid, xhr, args) {
            var assertions = JSON.parse(xhr.responseText);
            var cell = args.cell;
            var html = '';

            Y.Array.each(assertions, Y.bind(function(assertion, index) {
                assertion.id = args.userid + '-' + index;
                html += this.templates.badge(assertion);
                this.assertions[assertion.id] = assertion;
            }, this));

            cell.setContent(this.templates.list({content: html}));
            args.callback();
        },
        // Copied from YUI 3.8.0 templates to work with Moodle 2.2
        compile_template: function(text, options) {

            var blocks = [],
                    tokenClose = "\uffff",
                    tokenOpen = "\ufffe",
                    source;

            options = Y.merge({
                code: /\{\{%([\s\S]+?)%\}\}/g,
                escapedOutput: /\{\{(?!%)([\s\S]+?)\}\}/g,
                rawOutput: /\{\{\{([\s\S]+?)\}\}\}/g,
                stringEscape: /\\|'|\r|\n|\t|\u2028|\u2029/g,
                stringReplace: {
                    '\\': '\\\\',
                    "'": "\\'",
                    '\r': '\\r',
                    '\n': '\\n',
                    '\t': '\\t',
                    '\u2028': '\\u2028',
                    '\u2029': '\\u2029'
        }
            }, options);

            source = "var $b='', $v=function (v){return v || v === 0 ? v : $b;}, $t='" +
                    text.replace(/\ufffe|\uffff/g, '')
                    .replace(options.rawOutput, function(match, code) {
                        return tokenOpen + (blocks.push("'+\n$v(" + code + ")+\n'") - 1) + tokenClose;
                    })
                    .replace(options.escapedOutput, function(match, code) {
                        return tokenOpen + (blocks.push("'+\n$e($v(" + code + "))+\n'") - 1) + tokenClose;
                    })
                    .replace(options.code, function(match, code) {
                        return tokenOpen + (blocks.push("';\n" + code + "\n$t+='") - 1) + tokenClose;
                    })
                    .replace(options.stringEscape, function(match) {
                        return options.stringReplace[match] || '';
                    })
                    .replace(/\ufffe(\d+)\uffff/g, function(match, index) {
                        return blocks[parseInt(index, 10)];
                    })
                    .replace(/\n\$t\+='';\n/g, '\n') + "';\nreturn $t;";

            // If compile() was called from precompile(), return precompiled source.
            if (options.precompile) {
                return "function (Y, $e, data) {\n" + source + "\n}";
            }

            // Otherwise, return an executable function.
            return this.revive_template(new Function('Y', '$e', 'data', source));
        },

        // Copied from YUI 3.8.0
        revive_template: function(precompiled) {
            return function(data) {
                data || (data = {});
                return precompiled.call(data, Y, Y.Escape.html, data);
            };
        }
    }, {
        NAME: DISPLAYERNAME,
        ATTRS: {
            aparam: {}
        }
    });

    M.local_obf = M.local_obf || {};
    M.local_obf.init_courseuserbadgedisplayer = function(config) {
        return new COURSEUSERBADGEDISPLAYER(config);
    };

    M.local_obf.init_badgedisplayer = function(config) {
        config.init_list_only = true;
        return new COURSEUSERBADGEDISPLAYER(config);
    };
}, '@VERSION@', {requires: ['io-base', 'json-parse', 'panel', 'escape', 'widget-buttons', 'widget-stdmod']});
