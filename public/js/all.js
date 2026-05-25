!function (e) { e(["jquery"], function (e) { return function () { function t(e, t, n) { return g({ type: O.error, iconClass: m().iconClasses.error, message: e, optionsOverride: n, title: t }) } function n(t, n) { return t || (t = m()), v = e("#" + t.containerId), v.length ? v : (n && (v = d(t)), v) } function o(e, t, n) { return g({ type: O.info, iconClass: m().iconClasses.info, message: e, optionsOverride: n, title: t }) } function s(e) { C = e } function i(e, t, n) { return g({ type: O.success, iconClass: m().iconClasses.success, message: e, optionsOverride: n, title: t }) } function a(e, t, n) { return g({ type: O.warning, iconClass: m().iconClasses.warning, message: e, optionsOverride: n, title: t }) } function r(e, t) { var o = m(); v || n(o), u(e, o, t) || l(o) } function c(t) { var o = m(); return v || n(o), t && 0 === e(":focus", t).length ? void h(t) : void (v.children().length && v.remove()) } function l(t) { for (var n = v.children(), o = n.length - 1; o >= 0; o--)u(e(n[o]), t) } function u(t, n, o) { var s = !(!o || !o.force) && o.force; return !(!t || !s && 0 !== e(":focus", t).length) && (t[n.hideMethod]({ duration: n.hideDuration, easing: n.hideEasing, complete: function () { h(t) } }), !0) } function d(t) { return v = e("<div/>").attr("id", t.containerId).addClass(t.positionClass), v.appendTo(e(t.target)), v } function p() { return { tapToDismiss: !0, toastClass: "toast", containerId: "toast-container", debug: !1, showMethod: "fadeIn", showDuration: 300, showEasing: "swing", onShown: void 0, hideMethod: "fadeOut", hideDuration: 1e3, hideEasing: "swing", onHidden: void 0, closeMethod: !1, closeDuration: !1, closeEasing: !1, closeOnHover: !0, extendedTimeOut: 1e3, iconClasses: { error: "toast-error", info: "toast-info", success: "toast-success", warning: "toast-warning" }, iconClass: "toast-info", positionClass: "toast-top-right", timeOut: 5e3, titleClass: "toast-title", messageClass: "toast-message", escapeHtml: !1, target: "body", closeHtml: '<button type="button">&times;</button>', closeClass: "toast-close-button", newestOnTop: !0, preventDuplicates: !1, progressBar: !1, progressClass: "toast-progress", rtl: !1 } } function f(e) { C && C(e) } function g(t) { function o(e) { return null == e && (e = ""), e.replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/'/g, "&#39;").replace(/</g, "&lt;").replace(/>/g, "&gt;") } function s() { c(), u(), d(), p(), g(), C(), l(), i() } function i() { var e = ""; switch (t.iconClass) { case "toast-success": case "toast-info": e = "polite"; break; default: e = "assertive" }I.attr("aria-live", e) } function a() { E.closeOnHover && I.hover(H, D), !E.onclick && E.tapToDismiss && I.click(b), E.closeButton && j && j.click(function (e) { e.stopPropagation ? e.stopPropagation() : void 0 !== e.cancelBubble && e.cancelBubble !== !0 && (e.cancelBubble = !0), E.onCloseClick && E.onCloseClick(e), b(!0) }), E.onclick && I.click(function (e) { E.onclick(e), b() }) } function r() { I.hide(), I[E.showMethod]({ duration: E.showDuration, easing: E.showEasing, complete: E.onShown }), E.timeOut > 0 && (k = setTimeout(b, E.timeOut), F.maxHideTime = parseFloat(E.timeOut), F.hideEta = (new Date).getTime() + F.maxHideTime, E.progressBar && (F.intervalId = setInterval(x, 10))) } function c() { t.iconClass && I.addClass(E.toastClass).addClass(y) } function l() { E.newestOnTop ? v.prepend(I) : v.append(I) } function u() { if (t.title) { var e = t.title; E.escapeHtml && (e = o(t.title)), M.append(e).addClass(E.titleClass), I.append(M) } } function d() { if (t.message) { var e = t.message; E.escapeHtml && (e = o(t.message)), B.append(e).addClass(E.messageClass), I.append(B) } } function p() { E.closeButton && (j.addClass(E.closeClass).attr("role", "button"), I.prepend(j)) } function g() { E.progressBar && (q.addClass(E.progressClass), I.prepend(q)) } function C() { E.rtl && I.addClass("rtl") } function O(e, t) { if (e.preventDuplicates) { if (t.message === w) return !0; w = t.message } return !1 } function b(t) { var n = t && E.closeMethod !== !1 ? E.closeMethod : E.hideMethod, o = t && E.closeDuration !== !1 ? E.closeDuration : E.hideDuration, s = t && E.closeEasing !== !1 ? E.closeEasing : E.hideEasing; if (!e(":focus", I).length || t) return clearTimeout(F.intervalId), I[n]({ duration: o, easing: s, complete: function () { h(I), clearTimeout(k), E.onHidden && "hidden" !== P.state && E.onHidden(), P.state = "hidden", P.endTime = new Date, f(P) } }) } function D() { (E.timeOut > 0 || E.extendedTimeOut > 0) && (k = setTimeout(b, E.extendedTimeOut), F.maxHideTime = parseFloat(E.extendedTimeOut), F.hideEta = (new Date).getTime() + F.maxHideTime) } function H() { clearTimeout(k), F.hideEta = 0, I.stop(!0, !0)[E.showMethod]({ duration: E.showDuration, easing: E.showEasing }) } function x() { var e = (F.hideEta - (new Date).getTime()) / F.maxHideTime * 100; q.width(e + "%") } var E = m(), y = t.iconClass || E.iconClass; if ("undefined" != typeof t.optionsOverride && (E = e.extend(E, t.optionsOverride), y = t.optionsOverride.iconClass || y), !O(E, t)) { T++, v = n(E, !0); var k = null, I = e("<div/>"), M = e("<div/>"), B = e("<div/>"), q = e("<div/>"), j = e(E.closeHtml), F = { intervalId: null, hideEta: null, maxHideTime: null }, P = { toastId: T, state: "visible", startTime: new Date, options: E, map: t }; return s(), r(), a(), f(P), E.debug && console && console.log(P), I } } function m() { return e.extend({}, p(), b.options) } function h(e) { v || (v = n()), e.is(":visible") || (e.remove(), e = null, 0 === v.children().length && (v.remove(), w = void 0)) } var v, C, w, T = 0, O = { error: "error", info: "info", success: "success", warning: "warning" }, b = { clear: r, remove: c, error: t, getContainer: n, info: o, options: {}, subscribe: s, success: i, version: "2.1.3", warning: a }; return b }() }) }("function" == typeof define && define.amd ? define : function (e, t) { "undefined" != typeof module && module.exports ? module.exports = t(require("jquery")) : window.toastr = t(window.jQuery) });

/*!
 * Datepicker for Bootstrap v1.9.0 (https://github.com/uxsolutions/bootstrap-datepicker)
 *
 * Licensed under the Apache License v2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

!function (a) { "function" == typeof define && define.amd ? define(["jquery"], a) : a("object" == typeof exports ? require("jquery") : jQuery) }(function (a, b) {
    function c() { return new Date(Date.UTC.apply(Date, arguments)) } function d() { var a = new Date; return c(a.getFullYear(), a.getMonth(), a.getDate()) } function e(a, b) { return a.getUTCFullYear() === b.getUTCFullYear() && a.getUTCMonth() === b.getUTCMonth() && a.getUTCDate() === b.getUTCDate() } function f(c, d) { return function () { return d !== b && a.fn.datepicker.deprecated(d), this[c].apply(this, arguments) } } function g(a) { return a && !isNaN(a.getTime()) } function h(b, c) { function d(a, b) { return b.toLowerCase() } var e, f = a(b).data(), g = {}, h = new RegExp("^" + c.toLowerCase() + "([A-Z])"); c = new RegExp("^" + c.toLowerCase()); for (var i in f) c.test(i) && (e = i.replace(h, d), g[e] = f[i]); return g } function i(b) { var c = {}; if (q[b] || (b = b.split("-")[0], q[b])) { var d = q[b]; return a.each(p, function (a, b) { b in d && (c[b] = d[b]) }), c } } var j = function () { var b = { get: function (a) { return this.slice(a)[0] }, contains: function (a) { for (var b = a && a.valueOf(), c = 0, d = this.length; c < d; c++)if (0 <= this[c].valueOf() - b && this[c].valueOf() - b < 864e5) return c; return -1 }, remove: function (a) { this.splice(a, 1) }, replace: function (b) { b && (a.isArray(b) || (b = [b]), this.clear(), this.push.apply(this, b)) }, clear: function () { this.length = 0 }, copy: function () { var a = new j; return a.replace(this), a } }; return function () { var c = []; return c.push.apply(c, arguments), a.extend(c, b), c } }(), k = function (b, c) { a.data(b, "datepicker", this), this._events = [], this._secondaryEvents = [], this._process_options(c), this.dates = new j, this.viewDate = this.o.defaultViewDate, this.focusDate = null, this.element = a(b), this.isInput = this.element.is("input"), this.inputField = this.isInput ? this.element : this.element.find("input"), this.component = !!this.element.hasClass("date") && this.element.find(".add-on, .input-group-addon, .input-group-append, .input-group-prepend, .btn"), this.component && 0 === this.component.length && (this.component = !1), this.isInline = !this.component && this.element.is("div"), this.picker = a(r.template), this._check_template(this.o.templates.leftArrow) && this.picker.find(".prev").html(this.o.templates.leftArrow), this._check_template(this.o.templates.rightArrow) && this.picker.find(".next").html(this.o.templates.rightArrow), this._buildEvents(), this._attachEvents(), this.isInline ? this.picker.addClass("datepicker-inline").appendTo(this.element) : this.picker.addClass("datepicker-dropdown dropdown-menu"), this.o.rtl && this.picker.addClass("datepicker-rtl"), this.o.calendarWeeks && this.picker.find(".datepicker-days .datepicker-switch, thead .datepicker-title, tfoot .today, tfoot .clear").attr("colspan", function (a, b) { return Number(b) + 1 }), this._process_options({ startDate: this._o.startDate, endDate: this._o.endDate, daysOfWeekDisabled: this.o.daysOfWeekDisabled, daysOfWeekHighlighted: this.o.daysOfWeekHighlighted, datesDisabled: this.o.datesDisabled }), this._allow_update = !1, this.setViewMode(this.o.startView), this._allow_update = !0, this.fillDow(), this.fillMonths(), this.update(), this.isInline && this.show() }; k.prototype = { constructor: k, _resolveViewName: function (b) { return a.each(r.viewModes, function (c, d) { if (b === c || -1 !== a.inArray(b, d.names)) return b = c, !1 }), b }, _resolveDaysOfWeek: function (b) { return a.isArray(b) || (b = b.split(/[,\s]*/)), a.map(b, Number) }, _check_template: function (c) { try { if (c === b || "" === c) return !1; if ((c.match(/[<>]/g) || []).length <= 0) return !0; return a(c).length > 0 } catch (a) { return !1 } }, _process_options: function (b) { this._o = a.extend({}, this._o, b); var e = this.o = a.extend({}, this._o), f = e.language; q[f] || (f = f.split("-")[0], q[f] || (f = o.language)), e.language = f, e.startView = this._resolveViewName(e.startView), e.minViewMode = this._resolveViewName(e.minViewMode), e.maxViewMode = this._resolveViewName(e.maxViewMode), e.startView = Math.max(this.o.minViewMode, Math.min(this.o.maxViewMode, e.startView)), !0 !== e.multidate && (e.multidate = Number(e.multidate) || !1, !1 !== e.multidate && (e.multidate = Math.max(0, e.multidate))), e.multidateSeparator = String(e.multidateSeparator), e.weekStart %= 7, e.weekEnd = (e.weekStart + 6) % 7; var g = r.parseFormat(e.format); e.startDate !== -1 / 0 && (e.startDate ? e.startDate instanceof Date ? e.startDate = this._local_to_utc(this._zero_time(e.startDate)) : e.startDate = r.parseDate(e.startDate, g, e.language, e.assumeNearbyYear) : e.startDate = -1 / 0), e.endDate !== 1 / 0 && (e.endDate ? e.endDate instanceof Date ? e.endDate = this._local_to_utc(this._zero_time(e.endDate)) : e.endDate = r.parseDate(e.endDate, g, e.language, e.assumeNearbyYear) : e.endDate = 1 / 0), e.daysOfWeekDisabled = this._resolveDaysOfWeek(e.daysOfWeekDisabled || []), e.daysOfWeekHighlighted = this._resolveDaysOfWeek(e.daysOfWeekHighlighted || []), e.datesDisabled = e.datesDisabled || [], a.isArray(e.datesDisabled) || (e.datesDisabled = e.datesDisabled.split(",")), e.datesDisabled = a.map(e.datesDisabled, function (a) { return r.parseDate(a, g, e.language, e.assumeNearbyYear) }); var h = String(e.orientation).toLowerCase().split(/\s+/g), i = e.orientation.toLowerCase(); if (h = a.grep(h, function (a) { return /^auto|left|right|top|bottom$/.test(a) }), e.orientation = { x: "auto", y: "auto" }, i && "auto" !== i) if (1 === h.length) switch (h[0]) { case "top": case "bottom": e.orientation.y = h[0]; break; case "left": case "right": e.orientation.x = h[0] } else i = a.grep(h, function (a) { return /^left|right$/.test(a) }), e.orientation.x = i[0] || "auto", i = a.grep(h, function (a) { return /^top|bottom$/.test(a) }), e.orientation.y = i[0] || "auto"; else; if (e.defaultViewDate instanceof Date || "string" == typeof e.defaultViewDate) e.defaultViewDate = r.parseDate(e.defaultViewDate, g, e.language, e.assumeNearbyYear); else if (e.defaultViewDate) { var j = e.defaultViewDate.year || (new Date).getFullYear(), k = e.defaultViewDate.month || 0, l = e.defaultViewDate.day || 1; e.defaultViewDate = c(j, k, l) } else e.defaultViewDate = d() }, _applyEvents: function (a) { for (var c, d, e, f = 0; f < a.length; f++)c = a[f][0], 2 === a[f].length ? (d = b, e = a[f][1]) : 3 === a[f].length && (d = a[f][1], e = a[f][2]), c.on(e, d) }, _unapplyEvents: function (a) { for (var c, d, e, f = 0; f < a.length; f++)c = a[f][0], 2 === a[f].length ? (e = b, d = a[f][1]) : 3 === a[f].length && (e = a[f][1], d = a[f][2]), c.off(d, e) }, _buildEvents: function () { var b = { keyup: a.proxy(function (b) { -1 === a.inArray(b.keyCode, [27, 37, 39, 38, 40, 32, 13, 9]) && this.update() }, this), keydown: a.proxy(this.keydown, this), paste: a.proxy(this.paste, this) }; !0 === this.o.showOnFocus && (b.focus = a.proxy(this.show, this)), this.isInput ? this._events = [[this.element, b]] : this.component && this.inputField.length ? this._events = [[this.inputField, b], [this.component, { click: a.proxy(this.show, this) }]] : this._events = [[this.element, { click: a.proxy(this.show, this), keydown: a.proxy(this.keydown, this) }]], this._events.push([this.element, "*", { blur: a.proxy(function (a) { this._focused_from = a.target }, this) }], [this.element, { blur: a.proxy(function (a) { this._focused_from = a.target }, this) }]), this.o.immediateUpdates && this._events.push([this.element, { "changeYear changeMonth": a.proxy(function (a) { this.update(a.date) }, this) }]), this._secondaryEvents = [[this.picker, { click: a.proxy(this.click, this) }], [this.picker, ".prev, .next", { click: a.proxy(this.navArrowsClick, this) }], [this.picker, ".day:not(.disabled)", { click: a.proxy(this.dayCellClick, this) }], [a(window), { resize: a.proxy(this.place, this) }], [a(document), { "mousedown touchstart": a.proxy(function (a) { this.element.is(a.target) || this.element.find(a.target).length || this.picker.is(a.target) || this.picker.find(a.target).length || this.isInline || this.hide() }, this) }]] }, _attachEvents: function () { this._detachEvents(), this._applyEvents(this._events) }, _detachEvents: function () { this._unapplyEvents(this._events) }, _attachSecondaryEvents: function () { this._detachSecondaryEvents(), this._applyEvents(this._secondaryEvents) }, _detachSecondaryEvents: function () { this._unapplyEvents(this._secondaryEvents) }, _trigger: function (b, c) { var d = c || this.dates.get(-1), e = this._utc_to_local(d); this.element.trigger({ type: b, date: e, viewMode: this.viewMode, dates: a.map(this.dates, this._utc_to_local), format: a.proxy(function (a, b) { 0 === arguments.length ? (a = this.dates.length - 1, b = this.o.format) : "string" == typeof a && (b = a, a = this.dates.length - 1), b = b || this.o.format; var c = this.dates.get(a); return r.formatDate(c, b, this.o.language) }, this) }) }, show: function () { if (!(this.inputField.is(":disabled") || this.inputField.prop("readonly") && !1 === this.o.enableOnReadonly)) return this.isInline || this.picker.appendTo(this.o.container), this.place(), this.picker.show(), this._attachSecondaryEvents(), this._trigger("show"), (window.navigator.msMaxTouchPoints || "ontouchstart" in document) && this.o.disableTouchKeyboard && a(this.element).blur(), this }, hide: function () { return this.isInline || !this.picker.is(":visible") ? this : (this.focusDate = null, this.picker.hide().detach(), this._detachSecondaryEvents(), this.setViewMode(this.o.startView), this.o.forceParse && this.inputField.val() && this.setValue(), this._trigger("hide"), this) }, destroy: function () { return this.hide(), this._detachEvents(), this._detachSecondaryEvents(), this.picker.remove(), delete this.element.data().datepicker, this.isInput || delete this.element.data().date, this }, paste: function (b) { var c; if (b.originalEvent.clipboardData && b.originalEvent.clipboardData.types && -1 !== a.inArray("text/plain", b.originalEvent.clipboardData.types)) c = b.originalEvent.clipboardData.getData("text/plain"); else { if (!window.clipboardData) return; c = window.clipboardData.getData("Text") } this.setDate(c), this.update(), b.preventDefault() }, _utc_to_local: function (a) { if (!a) return a; var b = new Date(a.getTime() + 6e4 * a.getTimezoneOffset()); return b.getTimezoneOffset() !== a.getTimezoneOffset() && (b = new Date(a.getTime() + 6e4 * b.getTimezoneOffset())), b }, _local_to_utc: function (a) { return a && new Date(a.getTime() - 6e4 * a.getTimezoneOffset()) }, _zero_time: function (a) { return a && new Date(a.getFullYear(), a.getMonth(), a.getDate()) }, _zero_utc_time: function (a) { return a && c(a.getUTCFullYear(), a.getUTCMonth(), a.getUTCDate()) }, getDates: function () { return a.map(this.dates, this._utc_to_local) }, getUTCDates: function () { return a.map(this.dates, function (a) { return new Date(a) }) }, getDate: function () { return this._utc_to_local(this.getUTCDate()) }, getUTCDate: function () { var a = this.dates.get(-1); return a !== b ? new Date(a) : null }, clearDates: function () { this.inputField.val(""), this.update(), this._trigger("changeDate"), this.o.autoclose && this.hide() }, setDates: function () { var b = a.isArray(arguments[0]) ? arguments[0] : arguments; return this.update.apply(this, b), this._trigger("changeDate"), this.setValue(), this }, setUTCDates: function () { var b = a.isArray(arguments[0]) ? arguments[0] : arguments; return this.setDates.apply(this, a.map(b, this._utc_to_local)), this }, setDate: f("setDates"), setUTCDate: f("setUTCDates"), remove: f("destroy", "Method `remove` is deprecated and will be removed in version 2.0. Use `destroy` instead"), setValue: function () { var a = this.getFormattedDate(); return this.inputField.val(a), this }, getFormattedDate: function (c) { c === b && (c = this.o.format); var d = this.o.language; return a.map(this.dates, function (a) { return r.formatDate(a, c, d) }).join(this.o.multidateSeparator) }, getStartDate: function () { return this.o.startDate }, setStartDate: function (a) { return this._process_options({ startDate: a }), this.update(), this.updateNavArrows(), this }, getEndDate: function () { return this.o.endDate }, setEndDate: function (a) { return this._process_options({ endDate: a }), this.update(), this.updateNavArrows(), this }, setDaysOfWeekDisabled: function (a) { return this._process_options({ daysOfWeekDisabled: a }), this.update(), this }, setDaysOfWeekHighlighted: function (a) { return this._process_options({ daysOfWeekHighlighted: a }), this.update(), this }, setDatesDisabled: function (a) { return this._process_options({ datesDisabled: a }), this.update(), this }, place: function () { if (this.isInline) return this; var b = this.picker.outerWidth(), c = this.picker.outerHeight(), d = a(this.o.container), e = d.width(), f = "body" === this.o.container ? a(document).scrollTop() : d.scrollTop(), g = d.offset(), h = [0]; this.element.parents().each(function () { var b = a(this).css("z-index"); "auto" !== b && 0 !== Number(b) && h.push(Number(b)) }); var i = Math.max.apply(Math, h) + this.o.zIndexOffset, j = this.component ? this.component.parent().offset() : this.element.offset(), k = this.component ? this.component.outerHeight(!0) : this.element.outerHeight(!1), l = this.component ? this.component.outerWidth(!0) : this.element.outerWidth(!1), m = j.left - g.left, n = j.top - g.top; "body" !== this.o.container && (n += f), this.picker.removeClass("datepicker-orient-top datepicker-orient-bottom datepicker-orient-right datepicker-orient-left"), "auto" !== this.o.orientation.x ? (this.picker.addClass("datepicker-orient-" + this.o.orientation.x), "right" === this.o.orientation.x && (m -= b - l)) : j.left < 0 ? (this.picker.addClass("datepicker-orient-left"), m -= j.left - 10) : m + b > e ? (this.picker.addClass("datepicker-orient-right"), m += l - b) : this.o.rtl ? this.picker.addClass("datepicker-orient-right") : this.picker.addClass("datepicker-orient-left"); var o, p = this.o.orientation.y; if ("auto" === p && (o = -f + n - c, p = o < 0 ? "bottom" : "top"), this.picker.addClass("datepicker-orient-" + p), "top" === p ? n -= c + parseInt(this.picker.css("padding-top")) : n += k, this.o.rtl) { var q = e - (m + l); this.picker.css({ top: n, right: q, zIndex: i }) } else this.picker.css({ top: n, left: m, zIndex: i }); return this }, _allow_update: !0, update: function () { if (!this._allow_update) return this; var b = this.dates.copy(), c = [], d = !1; return arguments.length ? (a.each(arguments, a.proxy(function (a, b) { b instanceof Date && (b = this._local_to_utc(b)), c.push(b) }, this)), d = !0) : (c = this.isInput ? this.element.val() : this.element.data("date") || this.inputField.val(), c = c && this.o.multidate ? c.split(this.o.multidateSeparator) : [c], delete this.element.data().date), c = a.map(c, a.proxy(function (a) { return r.parseDate(a, this.o.format, this.o.language, this.o.assumeNearbyYear) }, this)), c = a.grep(c, a.proxy(function (a) { return !this.dateWithinRange(a) || !a }, this), !0), this.dates.replace(c), this.o.updateViewDate && (this.dates.length ? this.viewDate = new Date(this.dates.get(-1)) : this.viewDate < this.o.startDate ? this.viewDate = new Date(this.o.startDate) : this.viewDate > this.o.endDate ? this.viewDate = new Date(this.o.endDate) : this.viewDate = this.o.defaultViewDate), d ? (this.setValue(), this.element.change()) : this.dates.length && String(b) !== String(this.dates) && d && (this._trigger("changeDate"), this.element.change()), !this.dates.length && b.length && (this._trigger("clearDate"), this.element.change()), this.fill(), this }, fillDow: function () { if (this.o.showWeekDays) { var b = this.o.weekStart, c = "<tr>"; for (this.o.calendarWeeks && (c += '<th class="cw">&#160;</th>'); b < this.o.weekStart + 7;)c += '<th class="dow', -1 !== a.inArray(b, this.o.daysOfWeekDisabled) && (c += " disabled"), c += '">' + q[this.o.language].daysMin[b++ % 7] + "</th>"; c += "</tr>", this.picker.find(".datepicker-days thead").append(c) } }, fillMonths: function () { for (var a, b = this._utc_to_local(this.viewDate), c = "", d = 0; d < 12; d++)a = b && b.getMonth() === d ? " focused" : "", c += '<span class="month' + a + '">' + q[this.o.language].monthsShort[d] + "</span>"; this.picker.find(".datepicker-months td").html(c) }, setRange: function (b) { b && b.length ? this.range = a.map(b, function (a) { return a.valueOf() }) : delete this.range, this.fill() }, getClassNames: function (b) { var c = [], f = this.viewDate.getUTCFullYear(), g = this.viewDate.getUTCMonth(), h = d(); return b.getUTCFullYear() < f || b.getUTCFullYear() === f && b.getUTCMonth() < g ? c.push("old") : (b.getUTCFullYear() > f || b.getUTCFullYear() === f && b.getUTCMonth() > g) && c.push("new"), this.focusDate && b.valueOf() === this.focusDate.valueOf() && c.push("focused"), this.o.todayHighlight && e(b, h) && c.push("today"), -1 !== this.dates.contains(b) && c.push("active"), this.dateWithinRange(b) || c.push("disabled"), this.dateIsDisabled(b) && c.push("disabled", "disabled-date"), -1 !== a.inArray(b.getUTCDay(), this.o.daysOfWeekHighlighted) && c.push("highlighted"), this.range && (b > this.range[0] && b < this.range[this.range.length - 1] && c.push("range"), -1 !== a.inArray(b.valueOf(), this.range) && c.push("selected"), b.valueOf() === this.range[0] && c.push("range-start"), b.valueOf() === this.range[this.range.length - 1] && c.push("range-end")), c }, _fill_yearsView: function (c, d, e, f, g, h, i) { for (var j, k, l, m = "", n = e / 10, o = this.picker.find(c), p = Math.floor(f / e) * e, q = p + 9 * n, r = Math.floor(this.viewDate.getFullYear() / n) * n, s = a.map(this.dates, function (a) { return Math.floor(a.getUTCFullYear() / n) * n }), t = p - n; t <= q + n; t += n)j = [d], k = null, t === p - n ? j.push("old") : t === q + n && j.push("new"), -1 !== a.inArray(t, s) && j.push("active"), (t < g || t > h) && j.push("disabled"), t === r && j.push("focused"), i !== a.noop && (l = i(new Date(t, 0, 1)), l === b ? l = {} : "boolean" == typeof l ? l = { enabled: l } : "string" == typeof l && (l = { classes: l }), !1 === l.enabled && j.push("disabled"), l.classes && (j = j.concat(l.classes.split(/\s+/))), l.tooltip && (k = l.tooltip)), m += '<span class="' + j.join(" ") + '"' + (k ? ' title="' + k + '"' : "") + ">" + t + "</span>"; o.find(".datepicker-switch").text(p + "-" + q), o.find("td").html(m) }, fill: function () { var e, f, g = new Date(this.viewDate), h = g.getUTCFullYear(), i = g.getUTCMonth(), j = this.o.startDate !== -1 / 0 ? this.o.startDate.getUTCFullYear() : -1 / 0, k = this.o.startDate !== -1 / 0 ? this.o.startDate.getUTCMonth() : -1 / 0, l = this.o.endDate !== 1 / 0 ? this.o.endDate.getUTCFullYear() : 1 / 0, m = this.o.endDate !== 1 / 0 ? this.o.endDate.getUTCMonth() : 1 / 0, n = q[this.o.language].today || q.en.today || "", o = q[this.o.language].clear || q.en.clear || "", p = q[this.o.language].titleFormat || q.en.titleFormat, s = d(), t = (!0 === this.o.todayBtn || "linked" === this.o.todayBtn) && s >= this.o.startDate && s <= this.o.endDate && !this.weekOfDateIsDisabled(s); if (!isNaN(h) && !isNaN(i)) { this.picker.find(".datepicker-days .datepicker-switch").text(r.formatDate(g, p, this.o.language)), this.picker.find("tfoot .today").text(n).css("display", t ? "table-cell" : "none"), this.picker.find("tfoot .clear").text(o).css("display", !0 === this.o.clearBtn ? "table-cell" : "none"), this.picker.find("thead .datepicker-title").text(this.o.title).css("display", "string" == typeof this.o.title && "" !== this.o.title ? "table-cell" : "none"), this.updateNavArrows(), this.fillMonths(); var u = c(h, i, 0), v = u.getUTCDate(); u.setUTCDate(v - (u.getUTCDay() - this.o.weekStart + 7) % 7); var w = new Date(u); u.getUTCFullYear() < 100 && w.setUTCFullYear(u.getUTCFullYear()), w.setUTCDate(w.getUTCDate() + 42), w = w.valueOf(); for (var x, y, z = []; u.valueOf() < w;) { if ((x = u.getUTCDay()) === this.o.weekStart && (z.push("<tr>"), this.o.calendarWeeks)) { var A = new Date(+u + (this.o.weekStart - x - 7) % 7 * 864e5), B = new Date(Number(A) + (11 - A.getUTCDay()) % 7 * 864e5), C = new Date(Number(C = c(B.getUTCFullYear(), 0, 1)) + (11 - C.getUTCDay()) % 7 * 864e5), D = (B - C) / 864e5 / 7 + 1; z.push('<td class="cw">' + D + "</td>") } y = this.getClassNames(u), y.push("day"); var E = u.getUTCDate(); this.o.beforeShowDay !== a.noop && (f = this.o.beforeShowDay(this._utc_to_local(u)), f === b ? f = {} : "boolean" == typeof f ? f = { enabled: f } : "string" == typeof f && (f = { classes: f }), !1 === f.enabled && y.push("disabled"), f.classes && (y = y.concat(f.classes.split(/\s+/))), f.tooltip && (e = f.tooltip), f.content && (E = f.content)), y = a.isFunction(a.uniqueSort) ? a.uniqueSort(y) : a.unique(y), z.push('<td class="' + y.join(" ") + '"' + (e ? ' title="' + e + '"' : "") + ' data-date="' + u.getTime().toString() + '">' + E + "</td>"), e = null, x === this.o.weekEnd && z.push("</tr>"), u.setUTCDate(u.getUTCDate() + 1) } this.picker.find(".datepicker-days tbody").html(z.join("")); var F = q[this.o.language].monthsTitle || q.en.monthsTitle || "Months", G = this.picker.find(".datepicker-months").find(".datepicker-switch").text(this.o.maxViewMode < 2 ? F : h).end().find("tbody span").removeClass("active"); if (a.each(this.dates, function (a, b) { b.getUTCFullYear() === h && G.eq(b.getUTCMonth()).addClass("active") }), (h < j || h > l) && G.addClass("disabled"), h === j && G.slice(0, k).addClass("disabled"), h === l && G.slice(m + 1).addClass("disabled"), this.o.beforeShowMonth !== a.noop) { var H = this; a.each(G, function (c, d) { var e = new Date(h, c, 1), f = H.o.beforeShowMonth(e); f === b ? f = {} : "boolean" == typeof f ? f = { enabled: f } : "string" == typeof f && (f = { classes: f }), !1 !== f.enabled || a(d).hasClass("disabled") || a(d).addClass("disabled"), f.classes && a(d).addClass(f.classes), f.tooltip && a(d).prop("title", f.tooltip) }) } this._fill_yearsView(".datepicker-years", "year", 10, h, j, l, this.o.beforeShowYear), this._fill_yearsView(".datepicker-decades", "decade", 100, h, j, l, this.o.beforeShowDecade), this._fill_yearsView(".datepicker-centuries", "century", 1e3, h, j, l, this.o.beforeShowCentury) } }, updateNavArrows: function () { if (this._allow_update) { var a, b, c = new Date(this.viewDate), d = c.getUTCFullYear(), e = c.getUTCMonth(), f = this.o.startDate !== -1 / 0 ? this.o.startDate.getUTCFullYear() : -1 / 0, g = this.o.startDate !== -1 / 0 ? this.o.startDate.getUTCMonth() : -1 / 0, h = this.o.endDate !== 1 / 0 ? this.o.endDate.getUTCFullYear() : 1 / 0, i = this.o.endDate !== 1 / 0 ? this.o.endDate.getUTCMonth() : 1 / 0, j = 1; switch (this.viewMode) { case 4: j *= 10; case 3: j *= 10; case 2: j *= 10; case 1: a = Math.floor(d / j) * j <= f, b = Math.floor(d / j) * j + j > h; break; case 0: a = d <= f && e <= g, b = d >= h && e >= i }this.picker.find(".prev").toggleClass("disabled", a), this.picker.find(".next").toggleClass("disabled", b) } }, click: function (b) { b.preventDefault(), b.stopPropagation(); var e, f, g, h; e = a(b.target), e.hasClass("datepicker-switch") && this.viewMode !== this.o.maxViewMode && this.setViewMode(this.viewMode + 1), e.hasClass("today") && !e.hasClass("day") && (this.setViewMode(0), this._setDate(d(), "linked" === this.o.todayBtn ? null : "view")), e.hasClass("clear") && this.clearDates(), e.hasClass("disabled") || (e.hasClass("month") || e.hasClass("year") || e.hasClass("decade") || e.hasClass("century")) && (this.viewDate.setUTCDate(1), f = 1, 1 === this.viewMode ? (h = e.parent().find("span").index(e), g = this.viewDate.getUTCFullYear(), this.viewDate.setUTCMonth(h)) : (h = 0, g = Number(e.text()), this.viewDate.setUTCFullYear(g)), this._trigger(r.viewModes[this.viewMode - 1].e, this.viewDate), this.viewMode === this.o.minViewMode ? this._setDate(c(g, h, f)) : (this.setViewMode(this.viewMode - 1), this.fill())), this.picker.is(":visible") && this._focused_from && this._focused_from.focus(), delete this._focused_from }, dayCellClick: function (b) { var c = a(b.currentTarget), d = c.data("date"), e = new Date(d); this.o.updateViewDate && (e.getUTCFullYear() !== this.viewDate.getUTCFullYear() && this._trigger("changeYear", this.viewDate), e.getUTCMonth() !== this.viewDate.getUTCMonth() && this._trigger("changeMonth", this.viewDate)), this._setDate(e) }, navArrowsClick: function (b) { var c = a(b.currentTarget), d = c.hasClass("prev") ? -1 : 1; 0 !== this.viewMode && (d *= 12 * r.viewModes[this.viewMode].navStep), this.viewDate = this.moveMonth(this.viewDate, d), this._trigger(r.viewModes[this.viewMode].e, this.viewDate), this.fill() }, _toggle_multidate: function (a) { var b = this.dates.contains(a); if (a || this.dates.clear(), -1 !== b ? (!0 === this.o.multidate || this.o.multidate > 1 || this.o.toggleActive) && this.dates.remove(b) : !1 === this.o.multidate ? (this.dates.clear(), this.dates.push(a)) : this.dates.push(a), "number" == typeof this.o.multidate) for (; this.dates.length > this.o.multidate;)this.dates.remove(0) }, _setDate: function (a, b) { b && "date" !== b || this._toggle_multidate(a && new Date(a)), (!b && this.o.updateViewDate || "view" === b) && (this.viewDate = a && new Date(a)), this.fill(), this.setValue(), b && "view" === b || this._trigger("changeDate"), this.inputField.trigger("change"), !this.o.autoclose || b && "date" !== b || this.hide() }, moveDay: function (a, b) { var c = new Date(a); return c.setUTCDate(a.getUTCDate() + b), c }, moveWeek: function (a, b) { return this.moveDay(a, 7 * b) }, moveMonth: function (a, b) { if (!g(a)) return this.o.defaultViewDate; if (!b) return a; var c, d, e = new Date(a.valueOf()), f = e.getUTCDate(), h = e.getUTCMonth(), i = Math.abs(b); if (b = b > 0 ? 1 : -1, 1 === i) d = -1 === b ? function () { return e.getUTCMonth() === h } : function () { return e.getUTCMonth() !== c }, c = h + b, e.setUTCMonth(c), c = (c + 12) % 12; else { for (var j = 0; j < i; j++)e = this.moveMonth(e, b); c = e.getUTCMonth(), e.setUTCDate(f), d = function () { return c !== e.getUTCMonth() } } for (; d();)e.setUTCDate(--f), e.setUTCMonth(c); return e }, moveYear: function (a, b) { return this.moveMonth(a, 12 * b) }, moveAvailableDate: function (a, b, c) { do { if (a = this[c](a, b), !this.dateWithinRange(a)) return !1; c = "moveDay" } while (this.dateIsDisabled(a)); return a }, weekOfDateIsDisabled: function (b) { return -1 !== a.inArray(b.getUTCDay(), this.o.daysOfWeekDisabled) }, dateIsDisabled: function (b) { return this.weekOfDateIsDisabled(b) || a.grep(this.o.datesDisabled, function (a) { return e(b, a) }).length > 0 }, dateWithinRange: function (a) { return a >= this.o.startDate && a <= this.o.endDate }, keydown: function (a) { if (!this.picker.is(":visible")) return void (40 !== a.keyCode && 27 !== a.keyCode || (this.show(), a.stopPropagation())); var b, c, d = !1, e = this.focusDate || this.viewDate; switch (a.keyCode) { case 27: this.focusDate ? (this.focusDate = null, this.viewDate = this.dates.get(-1) || this.viewDate, this.fill()) : this.hide(), a.preventDefault(), a.stopPropagation(); break; case 37: case 38: case 39: case 40: if (!this.o.keyboardNavigation || 7 === this.o.daysOfWeekDisabled.length) break; b = 37 === a.keyCode || 38 === a.keyCode ? -1 : 1, 0 === this.viewMode ? a.ctrlKey ? (c = this.moveAvailableDate(e, b, "moveYear")) && this._trigger("changeYear", this.viewDate) : a.shiftKey ? (c = this.moveAvailableDate(e, b, "moveMonth")) && this._trigger("changeMonth", this.viewDate) : 37 === a.keyCode || 39 === a.keyCode ? c = this.moveAvailableDate(e, b, "moveDay") : this.weekOfDateIsDisabled(e) || (c = this.moveAvailableDate(e, b, "moveWeek")) : 1 === this.viewMode ? (38 !== a.keyCode && 40 !== a.keyCode || (b *= 4), c = this.moveAvailableDate(e, b, "moveMonth")) : 2 === this.viewMode && (38 !== a.keyCode && 40 !== a.keyCode || (b *= 4), c = this.moveAvailableDate(e, b, "moveYear")), c && (this.focusDate = this.viewDate = c, this.setValue(), this.fill(), a.preventDefault()); break; case 13: if (!this.o.forceParse) break; e = this.focusDate || this.dates.get(-1) || this.viewDate, this.o.keyboardNavigation && (this._toggle_multidate(e), d = !0), this.focusDate = null, this.viewDate = this.dates.get(-1) || this.viewDate, this.setValue(), this.fill(), this.picker.is(":visible") && (a.preventDefault(), a.stopPropagation(), this.o.autoclose && this.hide()); break; case 9: this.focusDate = null, this.viewDate = this.dates.get(-1) || this.viewDate, this.fill(), this.hide() }d && (this.dates.length ? this._trigger("changeDate") : this._trigger("clearDate"), this.inputField.trigger("change")) }, setViewMode: function (a) { this.viewMode = a, this.picker.children("div").hide().filter(".datepicker-" + r.viewModes[this.viewMode].clsName).show(), this.updateNavArrows(), this._trigger("changeViewMode", new Date(this.viewDate)) } }; var l = function (b, c) { a.data(b, "datepicker", this), this.element = a(b), this.inputs = a.map(c.inputs, function (a) { return a.jquery ? a[0] : a }), delete c.inputs, this.keepEmptyValues = c.keepEmptyValues, delete c.keepEmptyValues, n.call(a(this.inputs), c).on("changeDate", a.proxy(this.dateUpdated, this)), this.pickers = a.map(this.inputs, function (b) { return a.data(b, "datepicker") }), this.updateDates() }; l.prototype = { updateDates: function () { this.dates = a.map(this.pickers, function (a) { return a.getUTCDate() }), this.updateRanges() }, updateRanges: function () { var b = a.map(this.dates, function (a) { return a.valueOf() }); a.each(this.pickers, function (a, c) { c.setRange(b) }) }, clearDates: function () { a.each(this.pickers, function (a, b) { b.clearDates() }) }, dateUpdated: function (c) { if (!this.updating) { this.updating = !0; var d = a.data(c.target, "datepicker"); if (d !== b) { var e = d.getUTCDate(), f = this.keepEmptyValues, g = a.inArray(c.target, this.inputs), h = g - 1, i = g + 1, j = this.inputs.length; if (-1 !== g) { if (a.each(this.pickers, function (a, b) { b.getUTCDate() || b !== d && f || b.setUTCDate(e) }), e < this.dates[h]) for (; h >= 0 && e < this.dates[h];)this.pickers[h--].setUTCDate(e); else if (e > this.dates[i]) for (; i < j && e > this.dates[i];)this.pickers[i++].setUTCDate(e); this.updateDates(), delete this.updating } } } }, destroy: function () { a.map(this.pickers, function (a) { a.destroy() }), a(this.inputs).off("changeDate", this.dateUpdated), delete this.element.data().datepicker }, remove: f("destroy", "Method `remove` is deprecated and will be removed in version 2.0. Use `destroy` instead") }; var m = a.fn.datepicker, n = function (c) { var d = Array.apply(null, arguments); d.shift(); var e; if (this.each(function () { var b = a(this), f = b.data("datepicker"), g = "object" == typeof c && c; if (!f) { var j = h(this, "date"), m = a.extend({}, o, j, g), n = i(m.language), p = a.extend({}, o, n, j, g); b.hasClass("input-daterange") || p.inputs ? (a.extend(p, { inputs: p.inputs || b.find("input").toArray() }), f = new l(this, p)) : f = new k(this, p), b.data("datepicker", f) } "string" == typeof c && "function" == typeof f[c] && (e = f[c].apply(f, d)) }), e === b || e instanceof k || e instanceof l) return this; if (this.length > 1) throw new Error("Using only allowed for the collection of a single element (" + c + " function)"); return e }; a.fn.datepicker = n; var o = a.fn.datepicker.defaults = { assumeNearbyYear: !1, autoclose: !1, beforeShowDay: a.noop, beforeShowMonth: a.noop, beforeShowYear: a.noop, beforeShowDecade: a.noop, beforeShowCentury: a.noop, calendarWeeks: !1, clearBtn: !1, toggleActive: !1, daysOfWeekDisabled: [], daysOfWeekHighlighted: [], datesDisabled: [], endDate: 1 / 0, forceParse: !0, format: "mm/dd/yyyy", keepEmptyValues: !1, keyboardNavigation: !0, language: "en", minViewMode: 0, maxViewMode: 4, multidate: !1, multidateSeparator: ",", orientation: "auto", rtl: !1, startDate: -1 / 0, startView: 0, todayBtn: !1, todayHighlight: !1, updateViewDate: !0, weekStart: 0, disableTouchKeyboard: !1, enableOnReadonly: !0, showOnFocus: !0, zIndexOffset: 10, container: "body", immediateUpdates: !1, title: "", templates: { leftArrow: "&#x00AB;", rightArrow: "&#x00BB;" }, showWeekDays: !0 }, p = a.fn.datepicker.locale_opts = ["format", "rtl", "weekStart"]; a.fn.datepicker.Constructor = k; var q = a.fn.datepicker.dates = { en: { days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"], daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"], daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"], months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"], monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"], today: "Today", clear: "Clear", titleFormat: "MM yyyy" } }, r = {
        viewModes: [{ names: ["days", "month"], clsName: "days", e: "changeMonth" }, { names: ["months", "year"], clsName: "months", e: "changeYear", navStep: 1 }, { names: ["years", "decade"], clsName: "years", e: "changeDecade", navStep: 10 }, { names: ["decades", "century"], clsName: "decades", e: "changeCentury", navStep: 100 }, { names: ["centuries", "millennium"], clsName: "centuries", e: "changeMillennium", navStep: 1e3 }], validParts: /dd?|DD?|mm?|MM?|yy(?:yy)?/g, nonpunctuation: /[^ -\/:-@\u5e74\u6708\u65e5\[-`{-~\t\n\r]+/g, parseFormat: function (a) { if ("function" == typeof a.toValue && "function" == typeof a.toDisplay) return a; var b = a.replace(this.validParts, "\0").split("\0"), c = a.match(this.validParts); if (!b || !b.length || !c || 0 === c.length) throw new Error("Invalid date format."); return { separators: b, parts: c } }, parseDate: function (c, e, f, g) { function h(a, b) { return !0 === b && (b = 10), a < 100 && (a += 2e3) > (new Date).getFullYear() + b && (a -= 100), a } function i() { var a = this.slice(0, j[n].length), b = j[n].slice(0, a.length); return a.toLowerCase() === b.toLowerCase() } if (!c) return b; if (c instanceof Date) return c; if ("string" == typeof e && (e = r.parseFormat(e)), e.toValue) return e.toValue(c, e, f); var j, l, m, n, o, p = { d: "moveDay", m: "moveMonth", w: "moveWeek", y: "moveYear" }, s = { yesterday: "-1d", today: "+0d", tomorrow: "+1d" }; if (c in s && (c = s[c]), /^[\-+]\d+[dmwy]([\s,]+[\-+]\d+[dmwy])*$/i.test(c)) { for (j = c.match(/([\-+]\d+)([dmwy])/gi), c = new Date, n = 0; n < j.length; n++)l = j[n].match(/([\-+]\d+)([dmwy])/i), m = Number(l[1]), o = p[l[2].toLowerCase()], c = k.prototype[o](c, m); return k.prototype._zero_utc_time(c) } j = c && c.match(this.nonpunctuation) || []; var t, u, v = {}, w = ["yyyy", "yy", "M", "MM", "m", "mm", "d", "dd"], x = { yyyy: function (a, b) { return a.setUTCFullYear(g ? h(b, g) : b) }, m: function (a, b) { if (isNaN(a)) return a; for (b -= 1; b < 0;)b += 12; for (b %= 12, a.setUTCMonth(b); a.getUTCMonth() !== b;)a.setUTCDate(a.getUTCDate() - 1); return a }, d: function (a, b) { return a.setUTCDate(b) } }; x.yy = x.yyyy, x.M = x.MM = x.mm = x.m, x.dd = x.d, c = d(); var y = e.parts.slice(); if (j.length !== y.length && (y = a(y).filter(function (b, c) { return -1 !== a.inArray(c, w) }).toArray()), j.length === y.length) { var z; for (n = 0, z = y.length; n < z; n++) { if (t = parseInt(j[n], 10), l = y[n], isNaN(t)) switch (l) { case "MM": u = a(q[f].months).filter(i), t = a.inArray(u[0], q[f].months) + 1; break; case "M": u = a(q[f].monthsShort).filter(i), t = a.inArray(u[0], q[f].monthsShort) + 1 }v[l] = t } var A, B; for (n = 0; n < w.length; n++)(B = w[n]) in v && !isNaN(v[B]) && (A = new Date(c), x[B](A, v[B]), isNaN(A) || (c = A)) } return c }, formatDate: function (b, c, d) { if (!b) return ""; if ("string" == typeof c && (c = r.parseFormat(c)), c.toDisplay) return c.toDisplay(b, c, d); var e = { d: b.getUTCDate(), D: q[d].daysShort[b.getUTCDay()], DD: q[d].days[b.getUTCDay()], m: b.getUTCMonth() + 1, M: q[d].monthsShort[b.getUTCMonth()], MM: q[d].months[b.getUTCMonth()], yy: b.getUTCFullYear().toString().substring(2), yyyy: b.getUTCFullYear() }; e.dd = (e.d < 10 ? "0" : "") + e.d, e.mm = (e.m < 10 ? "0" : "") + e.m, b = []; for (var f = a.extend([], c.separators), g = 0, h = c.parts.length; g <= h; g++)f.length && b.push(f.shift()), b.push(e[c.parts[g]]); return b.join("") },
        headTemplate: '<thead><tr><th colspan="7" class="datepicker-title"></th></tr><tr><th class="prev">' + o.templates.leftArrow + '</th><th colspan="5" class="datepicker-switch"></th><th class="next">' + o.templates.rightArrow + "</th></tr></thead>", contTemplate: '<tbody><tr><td colspan="7"></td></tr></tbody>', footTemplate: '<tfoot><tr><th colspan="7" class="today"></th></tr><tr><th colspan="7" class="clear"></th></tr></tfoot>'
    }; r.template = '<div class="datepicker"><div class="datepicker-days"><table class="table-condensed">' + r.headTemplate + "<tbody></tbody>" + r.footTemplate + '</table></div><div class="datepicker-months"><table class="table-condensed">' + r.headTemplate + r.contTemplate + r.footTemplate + '</table></div><div class="datepicker-years"><table class="table-condensed">' + r.headTemplate + r.contTemplate + r.footTemplate + '</table></div><div class="datepicker-decades"><table class="table-condensed">' + r.headTemplate + r.contTemplate + r.footTemplate + '</table></div><div class="datepicker-centuries"><table class="table-condensed">' + r.headTemplate + r.contTemplate + r.footTemplate + "</table></div></div>", a.fn.datepicker.DPGlobal = r, a.fn.datepicker.noConflict = function () { return a.fn.datepicker = m, this }, a.fn.datepicker.version = "1.9.0", a.fn.datepicker.deprecated = function (a) { var b = window.console; b && b.warn && b.warn("DEPRECATED: " + a) }, a(document).on("focus.datepicker.data-api click.datepicker.data-api", '[data-provide="datepicker"]', function (b) { var c = a(this); c.data("datepicker") || (b.preventDefault(), n.call(c, "show")) }), a(function () { n.call(a('[data-provide="datepicker-inline"]')) })
});


document.addEventListener("DOMContentLoaded", function () {
    $(".today.day").closest('tr').addClass('currentWeek');
});


let checked = 0;
let radio_number_length = 0, li_number = 0;
let url_list_region = base_url + 'spin/list_region';
let url_list_province = base_url + 'spin/list_province';

const FUNC = {
    ajax_load_more: function (_this, url) {
        /*data load more will insert before to $selector*/
        console.log(url);
        $.ajax({
            url: url,
            type: 'POST',
            dateType: 'html',
            beforeSend: function () {
                _this.append('<i class="icon-spinner fa-spin text-danger"></i>');
            },
            success: function (result) {
                _this.find('i').remove();
                let selector_show_content = '#ajax_content';
                let resultFind = $(result).find(selector_show_content).html();
                if (resultFind) {
                    _this.children('svg').remove();
                    _this.parent().children(selector_show_content).append(resultFind);
                };
            }
        });
        return false;
    },
    ajax_load_data: function (selector, url, data) {
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dateType: 'html',
            beforeSend: function () {
                selector.html('<div class="text-center"><i class="icon icon-loading123"></i></div>');
            },
            success: function (result) {
                $(selector).html(result);
            }
        });
        return false;
    }
};

const RESULT_FOOT = {
    event_click_radio_digit: function () {
        $(document).on('click', "table > tfoot [type='radio']", function () {
            let table = $(this).closest('table');
            let checked = radio_number_length = $(this).val();
            let numberSel = table.find('tr:not(:first-child) > td > span.text-number');
            numberSel.each(function (i, v) {
                let numberSelector = $(v);
                if (numberSelector.children('.fa-pulse').length == 0) {
                    let number = numberSelector.text();
                    if (checked == 0) {
                        numberSelector.text(number);
                    } else {
                        let numberHead = number.substring(0, number.length - checked);
                        let numberSelected = number.replace(numberHead, '<number class="d-none">' + numberHead + '</number>');
                        numberSelector.html(numberSelected);
                    }
                }
            })
        });
    },
    event_click_filter_number: function () {
        $(document).on("click", ".check-number ul > li", function () {
            let _this = $(this);
            _this.parent().find('li').removeClass('active');
            let article = _this.closest('article');
            let table = article.find('.result table');
            if (_this.text() === li_number) {
                li_number = 0;
                let numberSel = table.find('tr > td > span');
                numberSel.each(function (i, v) {
                    let numberSelector = $(v);
                    numberSelector.html(numberSelector.text());
                });
            } else {
                _this.addClass('active');
                let numberChecked = li_number = _this.text();
                let numberSel = table.find('tr > td > span');
                numberSel.each(function (i, v) {
                    let numberSelector = $(v);
                    if (numberSelector.children('.fa-pulse').length === 0) {
                        let number = numberSelector.text().trim();
                        let twoNumberTail = number.substr((number.length - 2), 2);
                        let NumberTail = number.slice(0, number.length - 2);
                        numberSelector.find('number').remove();
                        if (checked === 0) {
                            numberSelector.text(number);
                            if (twoNumberTail.indexOf(numberChecked) !== -1) {
                                let numberSelected = NumberTail + '<number class="numberTail">' + twoNumberTail + '</number>';
                                numberSelector.html(numberSelected);
                            }
                        } else {
                            let numberSelected = number;
                            if (twoNumberTail.indexOf(numberChecked) !== -1) {
                                numberSelected = NumberTail + '<number class="numberHead">' + twoNumberTail + '</number>';
                            }
                            let numberLast = number.length - checked;
                            let numberHead = number.substring(0, numberLast);
                            numberSelected = numberSelected.replace(numberHead, '<number style="display: none;">' + numberHead + '</number>');
                            numberSelector.html(numberSelected);
                        }
                    }
                })
            }
        });
    },
    init: function () {
        this.event_click_radio_digit();
        this.event_click_filter_number();
    }
};

/*==>><<==*/
const AJAX_RESULT = {
    load_more: function () {
        $(document).on("click", "button.btnLoadMore", function (e) {
            e.preventDefault();
            let _this = $(this);
            let total = _this.data('total');
            let limit = _this.data('limit');
            let page = parseInt(_this.attr('data-page'));
            if (total <= page * limit) {
                _this.attr('hidden', 'true');
            }
            let url_ajax = _this.data('url') + page;
            _this.attr('data-page', page + 1);
            FUNC.ajax_load_more(_this, url_ajax);
        });
    },
    load_loto: function (selector, col, number) {
        let twoNumber = number.substr(number.length - 2);
        let head = twoNumber.substr(0, 1);
        let tail = twoNumber.substr(1, 1);

        let nthChildTr = parseInt(head) + 1;
        if (isNaN(nthChildTr) == false) {
            selector.find('tbody > tr:nth-child(' + nthChildTr + ') > td:nth-child(' + col + ')').append('<span>' + tail + '</span>');
        }
        if (selector.hasClass('table-xslt-single-col')) {
            if (tail >= 0) {
                let nthChildTailTr = parseInt(tail) + 1;
                col = 4;
                selector.find('tbody > tr:nth-child(' + nthChildTailTr + ') > td:nth-child(' + col + ')').append('<span>' + head + '</span>');
            }
        }
    },
    clear_loto: function (selector) {
        selector.find('tbody > tr > td > span').remove();
    },
    socket_live: function () {
        let d = new Date();
        let n = d.getHours();
        let day = d.getDate();
        if (n >= 16 && n < 19) {
            $.ajax({
                url: base_url + 'result/ajax_live',
                data: null,
                type: 'post',
                dataType: 'json',
                success: function (res) {
                    AJAX_RESULT.push(res);
                }
            });
            let socket_sv = ['https://live.soicau247s.org'];
            let socket = io(socket_sv[Math.floor(Math.random() * socket_sv.length)], { secure: true });

            let keyStorage = "livexxs" + n + day;
            let resCache = localStorage.getItem(keyStorage);

            if (resCache !== null) {
                AJAX_RESULT.push(JSON.parse(resCache));
                let nnn = resCache.search(/\\"\\"/gm);
                if (nnn == -1) {
                    NOTI_LIVE.hide();
                } else {
                    NOTI_LIVE.loadNoti();
                }
            }

            socket.on('data', (res) => {
                localStorage.setItem(keyStorage, JSON.stringify(res));
                AJAX_RESULT.push(res);
            });
        } else {
            localStorage.clear();
        }
    },
    push: function (res) {
        if (!res)
            return;
        let d = new Date();
        let n = d.getHours();
        let mi = d.getMinutes();
        let m = 0;
        if (mi >= 13) {
            let checkLoading = $('.table-result .text-number > .icon-spinner');
            let checkLoadingRand = $('.table-result .text-number > span');
            if (checkLoading.length > 0 || checkLoadingRand.length > 0) {
                let tableResult = $('.table-result');
                if (tableResult.data('code') !== '') {
                    let article = checkLoading.closest('article');
                    if (checkLoading.length === 0 && checkLoadingRand.length > 0) {
                        article = checkLoadingRand.closest('article');
                    }
                    let tableResult = article.find('table.table-result');
                    /*        ----------------------------------------       */
                    let code = tableResult.data('code');
                    if (code == undefined)
                        return;
                    if ($.inArray(code, ['XSMN', 'XSMT']) === -1) { //Show single column
                        let tableLotoSelector = article.find('table.table-xslt-single-col');
                        AJAX_RESULT.clear_loto(tableLotoSelector);
                        if (res[code] !== undefined) {
                            $.each(res[code], function (i, oneResult) {
                                let data_result = JSON.parse(oneResult.data_result);
                                $.each(data_result, function (tr, valTr) {
                                    tr = tr + 1;
                                    $.each(valTr, function (keyNumber, number) {
                                        keyNumber = keyNumber + 1;
                                        let elNumber = tableResult.find('tbody > tr:nth-child(' + tr + ') > td > span.text-number:nth-child(' + keyNumber + ')');
                                        if (number.length > 0) {
                                            elNumber.text(number);
                                            AJAX_RESULT.load_loto(tableLotoSelector, 2, number);
                                            m = 0;
                                        } else {
                                            let checkEmpty = $('.table-result  > tbody td > span.text-number > span');
                                            if (m == 0) {
                                                if (code == 'XSMB') {
                                                    if (checkLoading.length <= 4) {
                                                        if (tr == 2 && checkEmpty.length == 0) {
                                                            elNumber.html('<span class="loadNumber"></span>');
                                                            m = 1;
                                                        }
                                                    } else {
                                                        if (tr > 2 && checkEmpty.length == 0) {
                                                            elNumber.html('<span class="loadNumber"></span>');
                                                            m = 1;
                                                        }
                                                    }
                                                } else {
                                                    if (checkEmpty.length == 0) {
                                                        elNumber.html('<span class="loadNumber"></span>');
                                                        m = 1;
                                                    }
                                                }
                                            }
                                        }
                                    })
                                })
                            })
                        }
                    } else { // load multi col
                        let tableLotoSelector = article.find('table.table-xslt-multi-col[data-code="' + code + '"]');
                        tableResult = article.find('table.table-result[data-code="' + code + '"]');
                        AJAX_RESULT.clear_loto(tableLotoSelector);
                        let td = 2;
                        if (res[code] !== undefined) {
                            $.each(res[code], function (codeChild, oneResult) {
                                let data_result = JSON.parse(oneResult[0].data_result);
                                $.each(data_result, function (tr, valTr) {
                                    tr = tr + 2;
                                    $.each(valTr, function (keyNumber, number) {
                                        keyNumber = keyNumber + 1;
                                        let elNumber = tableResult.find('tbody > tr:nth-child(' + tr + ') > td:nth-child(' + td + ') > span.text-number:nth-child(' + keyNumber + ')');
                                        if (number.length > 0) {
                                            elNumber.text(number);
                                            AJAX_RESULT.load_loto(tableLotoSelector, td, number);
                                        } else {
                                            let firstTD = tableResult.find('td:nth-child(' + td + ') > span.text-number > span');
                                            if (firstTD.length == 0) {
                                                elNumber.html('<span class="loadNumber"></span>');
                                            }
                                        }
                                    })
                                });
                                td++;
                            })
                        }
                    }
                }
            }
        }

        /*tat thong bao khi quay xong*/
        let hour = { 18: "XSMB", 17: "XSMT", 16: "XSMN" };
        let tmp = hour[n];
        let string = '';
        if (res[tmp] !== undefined) {
            if (n != 18) {
                $.each(res[tmp], function (k, i) {
                    string += i[0]['data_result'];
                });
                let nnn = string.search('\\"\\"');
                if (nnn == -1) {
                    NOTI_LIVE.hide();
                } else NOTI_LIVE.loadNoti();
            } else {
                string += res[tmp][0]['data_result'];
                let nnn = string.search('""');
                if (nnn == -1) {
                    NOTI_LIVE.hide();
                } else NOTI_LIVE.loadNoti();
            }
        }
    },

    init: function () {
        NOTI_LIVE.loadNoti();
        this.socket_live();
        this.load_more();
    }
};

const NOTI_LIVE = {
    timeEndCount: 13,
    timeEndLive: 35,
    hourLive: [16, 17, 18],
    loadNoti: function () {
        let date = new Date();
        let h = date.getHours();
        let m = date.getMinutes();
        if ($("#notiLive").length == 0 && $("table .icon-spinner").length == 0 && $.inArray(h, this.hourLive) >= 0 && m <= this.timeEndLive) {
            $("main").prepend("<div id='notiLive'></div>");
            $("#notiLive").load(base_url + "application/views/public/default/_block/_live.html");
        }
    },
    hide: function () {
        if ($("#notiLive").length == 0) {
            let intval = setInterval(function () {
                if ($("#notiLive").length > 0) {
                    $("#notiLive").remove();
                    clearInterval(intval);
                }
            }, 500)
        } else $("#notiLive").remove();
    },
    countDown: function () {
        let searchOclock = setInterval(function () {
            let oclock = $("#oclock");
            if (oclock.length > 0) {
                let date = new Date();
                let now = date.getTime();
                date.setMinutes(this.timeEndCount);
                date.setSeconds(0);
                let timeEnd = date.getTime();
                let timeStamp = (timeEnd - now) / 1000;
                timeStamp = parseInt(timeStamp);
                let inval = setInterval(function () {
                    if (timeStamp > 0) {
                        timeStamp = Math.round(timeStamp);
                        let nguyen = parseInt(timeStamp / 60);
                        let du = timeStamp % 60;
                        $("#oclock").html("Còn " + nguyen + ':' + du);
                        timeStamp--;
                    } else {
                        $("#oclock").remove();
                        clearInterval(inval);
                    }
                }, 1000);
                clearInterval(searchOclock);
            }
        }, 500)
    }
};
/*==>><<==*/

const LOAD_NUMBER = {
    random_number: function (length) {
        let list_number = '';
        for (let i = 1; i <= length; i++) {
            let rand = Math.floor(Math.random() * 10);
            list_number += '<strong>' + rand + '</strong>';
        }
        return list_number;
    },
    format_number: function (number) {
        let string = number.replace(/<\/strong>/g, "");
        string = string.replace(/<strong>/g, "");
        return string;
    },
    load_number: function () {
        setInterval(function () {
            let checkEmpty = $('.table-result  > tbody td > span.text-number > span');
            if (checkEmpty.length > 0) {
                checkEmpty.each(function () {
                    let max = $(this).parent().attr("nc");
                    let num = LOAD_NUMBER.random_number(max);
                    $(this).html(num);
                });
            }
        }, 100);
    },
    check_time: function () {
        let checkLoading = $('.table-result  > tbody td > span.text-number > .fa-pulse');
        let article = checkLoading.closest('article');
        let tableResult = article.find('table.table-result');
        let checkTableMulti = article.find('table.table-result-multi-col');

        /*==>> kiem tra <<==*/
        if (tableResult.length > 0) console.log('co bang dang quay');
        if (checkTableMulti.length > 0) {
            for (let td = 2; td <= 5; td++) {
                let checkRandomIsset = tableResult.find('td:nth-child(' + td + ') > span.text-number > span');
                if (checkRandomIsset.length === 0) {
                    $('td:nth-child(' + td + ') > span.text-number').each(function () {
                        checkRandomIsset = tableResult.find('td:nth-child(' + td + ') > span.text-number > span');
                        let checkItemRandomIsset = $(this).find('.fa-pulse');
                        if (checkItemRandomIsset.length === 1 && checkRandomIsset.length === 0) {
                            $(this).html(' <span class="loadNumber"></span>');
                            return false;
                        }
                    });
                }
            }
        } else {
            let checkTableMb = tableResult.data('code');
            if (checkTableMb === 'XSMB') {
                let tdFind = tableResult.find('td:nth-child(2) > span.text-number');
                let tdFindImg = tableResult.find('td:nth-child(2) > span.text-number > .fa-pulse');
                $(tdFind).each(function (idx) {
                    if (tdFindImg.length > 4 && idx > 3) {
                        let checkTdFindImg = $(this).find('.fa-pulse');
                        if (checkTdFindImg.length === 1) {
                            $(this).html(' <span class="loadNumber"></span>');
                            return false;
                        }
                    }
                });
            } else {
                let tdFind = tableResult.find('td:nth-child(2) > span.text-number');
                $(tdFind).each(function () {
                    let letCheckTdFind = $(this).find('.fa-pulse');
                    if (letCheckTdFind.length === 1) {
                        $(this).html('  <span class="loadNumber"></span>');
                        return false;
                    }
                });
            }
        }
    },
    check_load_number: function () {
        console.log('check load number :)) start ...');
        let d = new Date();
        let mi = d.getMinutes();
        if (mi <= 13) {
            let second = 0;
            let SInt_cln = setInterval(function () {
                second = second + 1;
                console.log('loading...(' + second + ')');
                d = new Date();
                mi = d.getMinutes();
                if (mi === 13) {
                    LOAD_NUMBER.check_time();
                    clearInterval(SInt_cln);
                }
            }, 1000);
        } else {
            console.log('F5 loading...');
            LOAD_NUMBER.check_time();
        }
    },
    init: function () {
        this.load_number();
    }
};

/*==>><<==*/

let DELAY = (function () {
    var queue = [];

    function processQueue() {
        if (queue.length > 0) {
            setTimeout(function () {
                queue.shift().callBack();
                processQueue();
            }, queue[0].delay);
        }
    }

    return function DELAY(delay, callBack) {
        queue.push({ delay: delay, callBack: callBack });

        if (queue.length === 1) {
            processQueue();
        }
    };
}());

var LOAD_SPIN = {
    tableResult: null,
    countRandom: 10,
    timeRandom: 100,
    timeDelayRandom: 2000,
    classSpin: '.icon-spinner',
    tagSpin: '<i class="icon-spinner fa-spin"></i>',
    classLoadNumber: '.loadNumber',
    tagLoadNumber: '<span class="loadNumber"></span>',
    classBtnSpin: '.btn_spin',
    random_number: function (length, selector, colLoto) {
        let number;
        let flag = 0;
        let run_random_number = setInterval(function () {
            if (flag < LOAD_SPIN.countRandom) {
                flag++;
                number = LOAD_NUMBER.random_number(length);
                selector.html(LOAD_SPIN.tagLoadNumber).find(LOAD_SPIN.classLoadNumber).html(number);
            } else {
                clearInterval(run_random_number);
                number = number.replace(/(<([^>]+)>)/gi, "");
                selector.html(number);
                LOAD_SPIN.show_loto(colLoto, number.toString());
            }
        }, LOAD_SPIN.timeRandom);
    },
    show_loto: function (col, number) {
        let twoNumber = number.substr(number.length - 2);
        let head = twoNumber.substr(0, 1);
        let tail = twoNumber.substr(1, 1);

        let nthChildTr = parseInt(head) + 1;
        LOAD_SPIN.tableLoto.find('tbody > tr:nth-child(' + nthChildTr + ') > td:nth-child(' + col + ')').append('<span>' + tail + '</span>');
        if (LOAD_SPIN.tableLoto.hasClass('spin-loto-xsmb')) {
            nthChildTr = parseInt(tail) + 1;
            LOAD_SPIN.tableLoto.find('tbody tr:nth-child(' + nthChildTr + ') td:nth-child(4)').append('<span>' + head + '</span>');
        }
        LOAD_SPIN.done();
    },
    show_result_MB: function (childTr) {
        LOAD_SPIN.tableResult.find('tbody > tr' + childTr).each(function () {
            let trElement = $(this);
            trElement.find('td').each(function (iTd) {
                $(this).find('span').each(function (iSpan) {
                    let spanElement = $(this);
                    let nc = spanElement.data('nc');
                    DELAY(LOAD_SPIN.timeDelayRandom, function (iTd) {
                        return function () {
                            let keyTD = iTd + 1;
                            return LOAD_SPIN.random_number(nc, spanElement, keyTD);
                        };
                    }(iTd, iSpan));
                });
            });
        });
    },
    show_result_MN: function (childTr) {
        LOAD_SPIN.tableResult.find('tbody > tr' + childTr).each(function () {
            let trElement = $(this);
            trElement.find('td').each(function (iTd) {
                $(this).find('.text-number').each(function (iSpan) {
                    let spanElement = $(this);
                    let nc = spanElement.data('nc');
                    DELAY(LOAD_SPIN.timeDelayRandom, function (iTd) {
                        return function () {
                            let keyTD = iTd + 1;
                            LOAD_SPIN.random_number(nc, spanElement, keyTD);
                        };
                    }(iTd, iSpan));
                });

            });
        });
    },
    resetTable: function () {
        LOAD_SPIN.tableResult.find('.text-number').html(LOAD_SPIN.tagSpin);
        LOAD_SPIN.tableLoto.find('td > span').remove();
    },
    offBtn: function () {
        $(LOAD_SPIN.classBtnSpin).prop('disabled', true);
    },
    done: function () {
        let checkIconSpin = LOAD_SPIN.tableResult.find(LOAD_SPIN.classSpin);
        let checkRandom = LOAD_SPIN.tableResult.find(LOAD_SPIN.classLoadNumber);
        if (LOAD_SPIN.tableResult.hasClass('table-result-single') && checkIconSpin.length == 0 && checkRandom.length == 0) {
            $(LOAD_SPIN.classBtnSpin).prop('disabled', false);
        } else if (checkIconSpin.length == 0 && checkRandom.length == 0) {
            $(LOAD_SPIN.classBtnSpin).prop('disabled', false);
        }
    },
    init: function () {
        $('button.btn_spin').click(function (e) {
            LOAD_SPIN.tableResult = $(this).closest('article').find('.table-flex-border > table');
            LOAD_SPIN.tableLoto = LOAD_SPIN.tableResult.closest('article').find('.loto > table');
            e.preventDefault();
            LOAD_SPIN.offBtn();
            LOAD_SPIN.resetTable();
            if (LOAD_SPIN.tableResult.hasClass('spin-xsmb')) {
                LOAD_SPIN.show_result_MB(':nth-child(n+3)');
                LOAD_SPIN.show_result_MB(':nth-child(2)');
            } else if (LOAD_SPIN.tableResult.hasClass('table-result-province')) {
                LOAD_SPIN.show_result_MN('');
            } else {
                LOAD_SPIN.show_result_MN(':not(:first-child)');
            }
        });
    },
};

const SPIN_RESULT = {
    load_ajax_radio: function (url, on_change) {
        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'JSON',
            success: function (data) {
                let selector = $('select#list-province');
                let url = window.location.origin + window.location.pathname;
                let option = '';
                $.each(data, function (key, value) {
                    let embed = window.location.pathname.split('/');
                    if (embed[1] == 'ket-qua') {
                        option += '<option class="list_option" parent-id="' + value['id'] + '" value="' + base_url + embed[1] + '/' + 'quay-thu-' + value['code'].toLowerCase() + '.html' + '">' + value['title'] + '</option>';
                    } else {
                        option += '<option class="list_option" parent-id="' + value['id'] + '" value="' + base_url + 'quay-thu-' + value['code'].toLowerCase() + '.html' + '">' + value['title'] + '</option>';
                    }
                });
                selector.html(option);

                if (on_change == false) {
                    selector.html(option).val(url);
                    let val = $('#list-province option:selected').attr('value');
                    ACTIVE.active(val);
                } else {
                    location.href = selector.val();
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            }
        });
        return false;
    },
    change_url: function () {
        let radio = $('input[name="quaythu"]');
        if (radio.length > 0) {
            let radio_checked = $('input[name="quaythu"]:checked');
            radio.change(function () {
                if ($(this).val() == 1) {
                    SPIN_RESULT.load_ajax_radio(url_list_region, true);
                } else {
                    SPIN_RESULT.load_ajax_radio(url_list_province, true);
                }
            });

            if (radio_checked.val() == 1) {
                SPIN_RESULT.load_ajax_radio(url_list_region, false);
            } else {
                SPIN_RESULT.load_ajax_radio(url_list_province, false);
            }
        }
    },
    selectOption: function () {
        let selector = $('#list-province');
        selector.change(function () {
            window.location.href = $(this).val();
        })
    },
    init: function () {
        SPIN_RESULT.change_url();
        SPIN_RESULT.selectOption();
    }
};

/* ==>><<== */

const STATISTIC = {
    changeSelected: function () {
        if ($('#selectProvince').length > 0 && typeof turnOnPostFormByProvince === 'undefined') {
            $(document).on({
                change: function () {
                    window.location.href = $(this).val();
                }
            }, '#selectProvince');
        }
    },
    search_checked: function () {
        let current_url = window.location.href;
        let option = $('#selectProvince optgroup option');
        $.each(option, function (k, v) {
            if (current_url == $(this).val()) {
                $(this).attr('selected', 'selected');
            }
        });
        let option2 = $('#selectProvince option');
        $.each(option2, function (k, v) {
            if (current_url == $(this).val()) {
                $(this).attr('selected', 'selected');
            }
        });
    },
    DBW_inputChecked: function () {
        let input = $(".checkbox-dbtuan input");
        if (input.length > 0) {
            input.click(function () {
                $.each(input, function (k, i) {
                    if ($(i).prop('checked')) {
                        $(".tbl_" + $(i).attr('id')).addClass('d-block');
                    } else $(".tbl_" + $(i).attr('id')).removeClass('d-block');
                })
            });
        }
    },
    DBM_show_digits: function () {
        $(document).on('click', '.checkbox-dbthang input', function () {
            if ($("#full_number:checked").length > 0) {
                $(".result_td span[hidden]").removeAttr('hidden');
            }
            ;
            if ($("#two_number:checked").length > 0) {
                $(".result_td span:first-child").attr('hidden', 'hidden');
            }
            ;
        })
    },
    searchForm: function () {
        $(document).on("click", "#btn-minus", function (e) {
            e.preventDefault();
            let parent = $(this).closest('.form-search');
            let rangeday;
            let min = parent.find('#range-day').attr('min');
            if (min === 'undefined') min = 1;
            if (parent.find('#range-day').length > 0) {
                rangeday = parent.find('#range-day').val();
                if (rangeday > min) {
                    parent.find('#range-day').val(rangeday - 1);
                } else {
                    if ($('input[name="so_cau"]').length > 0) {
                        toastr.warning('Số ngày chạy cầu bạn vừa chọn không có cầu nào');
                    } else {
                        toastr.warning('Khoảng ngày phải lớn hơn 1');
                    }
                }
            }
        });
        $(document).on("click", "#btn-plus", function (e) {
            e.preventDefault();
            let parent = $(this).closest('.form-search');
            let rangeday;
            let max = parent.find('#range-day').attr('max');
            if (max === undefined) max = 99;
            if (parent.find('#range-day').length > 0) {
                rangeday = parent.find('#range-day').val();
                if (rangeday < max) {
                    parent.find('#range-day').val(parseInt(rangeday) + parseInt(1));
                } else {
                    if ($('input[name="so_cau"]').length > 0) {
                        toastr.warning('Số ngày chạy cầu bạn vừa chọn không có cầu nào');
                    } else {
                        toastr.warning('Khoảng ngày phải nhỏ hơn 99');
                    }
                }
            }
        });
        $(document).on("click", "#btn-Statistic-search", function (e) {
            e.preventDefault();
            let code, rangeday, date_end, date_begin, year, month, dow, is_special;
            let parent = $(this).closest('.form-search');
            let url = $(this).data('url');
            if (parent.find('#selectProvince').length > 0) {
                code = parent.find('#selectProvince').find(':selected').data('code');
            }
            if (parent.find('#selectCode').length > 0) {
                code = parent.find('#selectCode').find(':selected').data('code');
            }
            if (parent.find('#range-day').length > 0) {
                rangeday = parent.find('#range-day').val();
                if (rangeday == undefined) rangeday = parent.find('#select-dow').find(':selected').val();
                $('#rangeday_value').html(rangeday);
            }
            if (parent.find('#select-dow').length > 0) {
                dow = parent.find('#select-dow').find(':selected').val();
            }
            if (parent.find('#dateEnd').length > 0) {
                date_end = parent.find('#dateEnd').val();
            }
            if (parent.find('#dateBegin').length > 0) {
                date_begin = parent.find('#dateBegin').val();
            }
            if (parent.find('#selectDate').length > 0) {
                year = parent.find('#selectDate').find(':selected').val();
            }
            if (parent.find('#selectMonth').length > 0) {
                month = parent.find('#selectMonth').find(':selected').val();
            }
            if (parent.find('#is_special').length > 0) {
                is_special = parent.find('#is_special:checked').length;
            }
            if (parent.find('.mutliSelect').length > 0) {
                let mutliSelect = $(".mutliSelect input");
                year = [];
                $.each(mutliSelect, function (k, i) {
                    if ($(i).prop('checked')) {
                        year.push($(i).val());
                    }
                })
            }
            let selector = $('#view_data');
            let data = {
                code: code,
                rangeday: rangeday,
                dow: dow,
                date_begin: date_begin,
                date_end: date_end,
                year: year,
                month: month,
                is_special: is_special
            };
            FUNC.ajax_load_data(selector, "/statistic/" + url, data);
        });
    },
    ajax_load_province: function (id, dayOfWeek) {
        let rs = [];
        $.ajax({
            url: base_url + 'soicau/ajax_load_provinc',
            data: {
                id: id,
                dayOfWeek: dayOfWeek
            },
            type: 'POST',
            dataType: 'JSON',
            async: false,
            success: function (data) {
                rs = data;
            },
            error: function (data) {
                console.log(data);
            }
        });
        return rs;
    },
    showXien23() {
        $('#selectXien23 input').click(function () {
            $('#xien_2, #xien_3').addClass('d-none');
            let idThis = $(this).val();
            $('#' + idThis).removeClass('d-none');
        });
    },
    init: function () {
        this.search_checked();
        this.DBW_inputChecked();
        this.DBM_show_digits();
        this.searchForm();
        this.changeSelected();
        this.showXien23();
    }
};

const TAN_SUAT_LO_TO = {
    tk_top: function () {
        $(document).on("click", ".table-tansuat-loto #tk_top > button", function () {
            let _this = $(this);
            _this.parent().find('button').removeClass('active');
            _this.closest('.table-tansuat-loto').find('tbody tr:last-child td button').removeClass('active');
            _this.closest('.table-tansuat-loto').find('tbody tr td:last-child button').removeClass('active');
            _this.addClass('active');
            let data = _this.data('id');
            TAN_SUAT_LO_TO.action(data);
        });
        $(document).on("click", ".table-tansuat-loto tbody tr:last-child td button", function () {
            let _this = $(this);
            let data = _this.data('id');
            let number = _this.data('number');
            let selector_tab1 = $(".table-tansuat-loto");
            _this.closest('.table-tansuat-loto').find('tbody tr:last-child td button').removeClass('active');
            _this.closest('.table-tansuat-loto').find('tbody tr td:last-child button').removeClass('active');
            _this.closest('.table-tansuat-loto').find('#tk_top > button').removeClass('active');
            _this.closest('.table-tansuat-loto').find('#tk_top > button:first-child').addClass('active');
            _this.addClass('active');
            TAN_SUAT_LO_TO.head_tail(data, number);

            selector_tab1.find('input').removeAttr('checked');
            selector_tab1.find('input').parent().find('span.checkbox').removeClass('active');
            selector_tab1.find('tbody tr td:nth-child(' + (number + 1) + ') span.checkbox').addClass('active');
        });
        $(document).on("click", ".table-tansuat-loto tbody tr td:last-child button", function () {
            let _this = $(this);
            let data = _this.data('id');
            let number = _this.data('number');
            let selector_tab1 = $(".table-tansuat-loto");
            _this.closest('.table-tansuat-loto').find('tbody tr td:last-child button').removeClass('active');
            _this.closest('.table-tansuat-loto').find('tbody tr:last-child td button').removeClass('active');
            _this.closest('.table-tansuat-loto').find('#tk_top > button').removeClass('active');
            _this.closest('.table-tansuat-loto').find('#tk_top > button:first-child').addClass('active');
            _this.addClass('active');
            TAN_SUAT_LO_TO.head_tail(data, number);

            selector_tab1.find('input').removeAttr('checked');
            selector_tab1.find('input').parent().find('span.checkbox').removeClass('active');
            _this.closest('tr').find('span.checkbox').addClass('active');
        });
        $(document).on("click", ".table-tansuat-loto tbody .check-radio", function () {
            $(".table-tansuat-duoi-loto tbody tr").addClass('d-none');
            $(".table-tansuat-duoi-loto tbody").removeClass('d-none');
            $('.table-tansuat-loto tbody .check-radio input[type="checkbox"]:checked').each(function () {
                let id = $(this).parent().attr('for').substr(-2, 2);
                $(".table-tansuat-duoi-loto").find('tbody tr[number="' + id + '"]').removeClass('d-none');
            });
        });
    },
    action: function (action) {
        let selector_tab = $(".table-tansuat-duoi-loto");
        let selector_tab1 = $(".table-tansuat-loto");
        selector_tab.find('tbody').removeClass('d-none');
        selector_tab.find('tbody tr').removeClass('d-none');
        //selector_tab1.find('input').removeAttr('checked');
        selector_tab1.find('input').prop('checked', true);
        selector_tab1.find('input').parent().find('span.checkbox').addClass('active');
        if (action === 'all_none') {
            selector_tab.find('tbody').addClass('d-none');
            selector_tab1.find('input').removeAttr('checked');
            selector_tab1.find('input').parent().find('span.checkbox').removeClass('active');
        }
        if (action === 'only_even') {
            selector_tab.find('tbody tr:odd').addClass('d-none');
            $.each(selector_tab1.find('tbody .form-check'), function (k, i) {
                let number = parseInt($(i).find('.ml-2').html());
                if (number % 2 !== 0) {
                    $(i).find('input').removeAttr('checked');
                    $(i).find('span.checkbox').removeClass('active');
                }
            })
        }
        if (action === 'only_odd') {
            selector_tab.find('tbody tr:even').addClass('d-none');
            $.each(selector_tab1.find('tbody .form-check'), function (k, i) {
                let number = parseInt($(i).find('.ml-2').html());
                if (number % 2 === 0) {
                    $(i).find('input').removeAttr('checked');
                    $(i).find('span.checkbox').removeClass('active');
                }
            })
        }
    },
    head_tail: function (data, number) {
        let selector_tab = $(".table-tansuat-duoi-loto");
        selector_tab.find('tbody').removeClass('d-none');
        selector_tab.find('tbody tr').removeClass('d-none');
        if (data === 'head') {
            $.each(selector_tab.find('tbody tr'), function (k, i) {
                let h = $(i).attr('number-head');
                if (parseInt(h) !== parseInt(number)) $(i).addClass('d-none');
            })

        }
        if (data === 'tail') {
            $.each(selector_tab.find('tbody tr'), function (k, i) {
                $.each(selector_tab.find('tbody tr'), function (k, i) {
                    let h = $(i).attr('number-tail');
                    if (parseInt(h) !== parseInt(number)) $(i).addClass('d-none');
                })
            })
        }
    },
    init: function () {
        TAN_SUAT_LO_TO.tk_top();
    }
};

/*--> menu<--*/

const MENU = {
    showMenuScroll: function () {
        $(window).scroll(function () {
            if ($(this).scrollTop() > ($(".header-top").height() + $(".banner").height())) {
                $('#menu').addClass('scroll-active');
                $(".back-top").addClass('d-block');
            } else {
                $('#menu').removeClass('scroll-active');
                $(".back-top").removeClass('d-block');
            }
        });
    },
    menu_search: function () {
        return;
        $(document).on('click', ".btn-search-menu", function () {
            let _this = $(this);
            let parent = _this.closest('.form-search');
            let keySearch = parent.find('input').val().trim();
            if (keySearch) _this.attr('type', 'submit');
            else $('.box-search-menu').toggleClass('d-none');
        });
    },
    init: function () {
        this.showMenuScroll();
        this.menu_search();
    }
};

/*==>> Active <<==*/

const ACTIVE = {
    active: function (current_url) {
        if (!current_url) current_url = location.href;
        // menu
        $(".header-menu").find('a[href="' + current_url + '"]').addClass('active');
        // $(".main-menu").find('a[href="' + current_url + '"]').parent().parent().parent().addClass('active');
        /*if ($('#nav-menu a[href="' + current_url + '"]').length == 0) {
            let arr_current_url = current_url.split('-');
            let url = current_url.replace(arr_current_url.pop(), '');
            url += 'xsmb.html';
            $(".main-menu").find('a[href="' + url + '"]').parent().addClass('active');
            $(".main-menu").find('a[href="' + url + '"]').parent().parent().parent().addClass('active');
        }*/
        // dayofweek
        $(".submenu2-bg").find('a[href="' + current_url + '"]').parent().addClass('active');
        // breadcrumb
        $(".breadcrumb").find('a[href="' + current_url + '"]').parent().addClass('active');
        $.each($("ul.breadcrumb a:not(:first)"), function (k, i) {
            current_url = $(i).attr('href');
            // dayofweek
            $(".submenu2-bg").find('a[href="' + current_url + '"]').parent().addClass('active');
        });
        $('.sub-menu:has(.active)').closest('li').find('>a').addClass('active');
    },
    scrollTop: function () {
        $('.back-top img').on('click', function (e) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: 0 }, 500, 'linear');
        });
    },
    init: function () {
        this.active();
        this.scrollTop();
    }
};

$(document).ready(function () {
    RESULT_FOOT.init();
    AJAX_RESULT.init();
    LOAD_SPIN.init();
    LOAD_NUMBER.init();
    SPIN_RESULT.init();
    STATISTIC.init();
    TAN_SUAT_LO_TO.init();
    MENU.init();
    VOTE.init();
    ACTIVE.init();
    if ($('#detectProvinceByDow').length === 1) {
        detectProvinceByDow();
    }
    postFormByProvince();

    setInterval(function () {
        let today = new Date();
        let time = today.getHours() + ":" + today.getMinutes();
        if (time === "16:1" || time === "17:1" || time === "18:1") {
            location.reload();
        }
    }, 1000 * 35);
});

const VOTE = {
    init: function () {
        let selector = $(".rateit");
        if (selector.length > 0) {
            selector.bind('rated', function (e) {
                e.preventDefault();
                let ri = $(this);
                let value = ri.rateit('value');
                let urlC = $('#voteLink').val();
                console.log(base_url + 'news/rate', urlC, value);
                $.ajax({
                    url: base_url + 'news/rate',
                    type: 'GET',
                    data: {
                        link: urlC,
                        star: value
                    },
                    success: function (data) {
                        console.log(data);
                        toastr[data.status](data.message);
                        if (data.avg) {
                            $('.count-rate').html(data.count_vote);
                            $('.avg-rate').html(data.avg);
                        }
                    },
                    errors: function () {
                        toastr.warning('Có lỗi xảy ra');
                    }
                });
            });
        }
    },
};

$(".dropdown dt a").on('click', function (e) {
    e.preventDefault();
    $(".dropdown dd ul").slideToggle('fast');
});

$(".dropdown dd ul li a").on('click', function (e) {
    e.preventDefault();
    $(".dropdown dd ul").hide();
});

$(document).bind('click', function (e) {
    var $clicked = $(e.target);
    if (!$clicked.parents().hasClass("dropdown")) $(".dropdown dd ul").hide();
});

$(function () {
    xoso.init();
});
let xosoconfig = {
    rootPath: '/'
};
let isrunning = false;
let loading = '<i class="icon-spinner fa-spin"></i>';
let xoso = {
    variables: {
        lotMsgListMN: 0,
        lotMsgListMT: 0,
        currentPage: 1
    },
    init: function () {
        this.events();
    },
    events: function () {

        $("#hover-number td").mouseout(function () {
            let id = $(this).parent().attr("data");
            $('#table-' + id + ' tbody tr td div').each(function (index, element) {
                let txt = $(element).html();
                let res = txt.split('<mark>');
                $(element).html(res);
            });
        });
        $("#hover-number td").mouseover(function () {
            let value = $(this).text();
            console.log('a: ' + value);
            let id = $(this).parent().attr("data");
            $('#table-' + id + ' tbody tr td div').each(function (index, element) {
                let txt = $(element).html();
                if (txt[txt.length - 1] == value || txt[txt.length - 2] == value)
                    $(element).html(txt.slice(0, txt.length - 2) + '<mark>' + txt.slice(txt.length - 2, txt.length) + '</mark>');
            });
        });
    },
    virtualPath: function (patch) {
        let host = window.location.protocol + '//' + window.location.host;
        return host + patch;
    },
    ajaxEvents: {
        OnComplete: function () {
            $(".ajaxLoading").html("");
            $('.btn.btn-red').prop('disabled', false).css('cursor', 'default');
        },
        OnSuccess: function (data, status, xhr) {
            if (status == 'success') {
                if (data.jsonFlag == true) {
                    if (data.jsonRetval.length > 0) {
                        xoso.dialog(null, 'Thông báo', data.jsonRetval, null, null, function () {
                            window.location.href = xosoconfig.rootPath + 'profile.html';
                        });
                    }
                } else {
                    if (data.jsonRetval.length > 0) {
                        xoso.dialog(null, 'Thông báo', data.jsonRetval, null, null, null);
                    }
                }
            } else {
                alert('Quý khách vui lòng thử lại sau.');
            }
        },
        OnFailure: function (data) {
            $(".ajaxLoading").html("");
            $('.btn.btn-red').prop('disabled', false).css('cursor', 'default');
        },

    },
    RunningQuayThuVietlott: 0,
    RunRandomMegaComplete: function (str) {
        xoso.RunningQuayThuVietlott = 0;
        isrunning = false;
        $('#btnStartOrStop_Mega645').html('Quay thử lại');
    },
    RunRandomPowerComplete: function (str) {
        xoso.RunningQuayThuVietlott = 0;
        isrunning = false;
        $('#btnStartOrStop_Power655').html('Quay thử lại');
    },
    RunRandomMax4DComplete: function (str) {
        xoso.RunningQuayThuVietlott = 0;
        isrunning = false;
        $('#btnStartOrStop_Max4D').html('Quay thử lại');
    },
    RunRandomPower: function () {
        xoso.RunningQuayThuVietlott = 1;
        isrunning = true;
        $('#btnStartOrStop_Power655').html('Đang quay thử');
        let animationTimer = null;
        let started = new Date().getTime();
        let duration = 2000;
        let arrRange = new Array();
        let itemRandom = null;
        let index = null;
        //add ket qua
        let teams = [00, 01, 02, 03, 04, 05, 06, 07, 08, 09, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
            21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44,
            45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55];

        itemRandom = xoso.getRandomMegaString(teams);
        arrRange.push(itemRandom);
        index = teams.indexOf(itemRandom);
        if (index > -1) {
            teams.splice(index, 1);
        }
        //teams = teams.remove(itemRandom);

        itemRandom = xoso.getRandomMegaString(teams);
        arrRange.push(itemRandom);
        index = teams.indexOf(itemRandom);
        if (index > -1) {
            teams.splice(index, 1);
        }

        itemRandom = xoso.getRandomMegaString(teams);
        arrRange.push(itemRandom);
        index = teams.indexOf(itemRandom);
        if (index > -1) {
            teams.splice(index, 1);
        }

        itemRandom = xoso.getRandomMegaString(teams);
        arrRange.push(itemRandom);
        index = teams.indexOf(itemRandom);
        if (index > -1) {
            teams.splice(index, 1);
        }

        itemRandom = xoso.getRandomMegaString(teams);
        arrRange.push(itemRandom);
        index = teams.indexOf(itemRandom);
        if (index > -1) {
            teams.splice(index, 1);
        }

        itemRandom = xoso.getRandomMegaString(teams);
        arrRange.push(itemRandom);
        index = teams.indexOf(itemRandom);
        if (index > -1) {
            teams.splice(index, 1);
        }

        itemRandom = xoso.getRandomMegaString(teams);
        arrRange.push(itemRandom);
        //chuyen tat ca ket qua ve anh gif
        for (let i = 0; i < arrRange.length; i++) {
            $('#power655_' + i).html('<i class="icon-spinner fa-spin"></i>');
        }
        //gan du lieu cho tung ket qua, moi ket qua cach nhau 2000
        for (let i = 0; i < arrRange.length; i++) {
            xoso.sethtmlPower('power655_' + i, arrRange[i], 2000 * i);
        }
    },
    RunRandomMax4D: function () {
        xoso.RunningQuayThuVietlott = 2;
        isrunning = true;
        $('#btnStartOrStop_Max4D').html('Đang quay thử');
        let animationTimer = null;
        let started = new Date().getTime();
        let duration = 2000;
        let arrRange = new Array();
        //add ket qua
        arrRange.push(xoso.getRandomString(4));
        arrRange.push(xoso.getRandomString(4));
        arrRange.push(xoso.getRandomString(4));
        arrRange.push(xoso.getRandomString(4));
        arrRange.push(xoso.getRandomString(4));
        arrRange.push(xoso.getRandomString(4));
        arrRange.push(xoso.getRandomString(4));
        arrRange.push(xoso.getRandomString(4));
        //chuyen tat ca ket qua ve anh gif
        for (let i = 0; i < arrRange.length; i++) {
            $('#max4d_' + i).html('<i class="icon-spinner fa-spin"></i>');
        }
        //gan du lieu cho tung ket qua, moi ket qua cach nhau 2000
        for (let i = 0; i < arrRange.length - 2; i++) {
            xoso.sethtmlMax4D('max4d_' + i, arrRange[i], 2000 * i);
        }
    },
    RunRandomMega: function () {
        xoso.RunningQuayThuVietlott = 3;
        isrunning = true;
        $('#btnStartOrStop_Mega645').html('Đang quay thử');
        let animationTimer = null;
        let started = new Date().getTime();
        let duration = 2000;
        let arrRange = new Array(); let itemRandom = null; let index = null;
        //add ket qua
        let teams = [00, 01, 02, 03, 04, 05, 06, 07, 08, 09, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
            21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45];

        itemRandom = xoso.getRandomMegaString(teams);
        arrRange.push(itemRandom);
        index = teams.indexOf(itemRandom);
        if (index > -1) {
            teams.splice(index, 1);
        }
        //teams = teams.remove(itemRandom);

        itemRandom = xoso.getRandomMegaString(teams);
        arrRange.push(itemRandom);
        index = teams.indexOf(itemRandom);
        if (index > -1) {
            teams.splice(index, 1);
        }

        itemRandom = xoso.getRandomMegaString(teams);
        arrRange.push(itemRandom);
        index = teams.indexOf(itemRandom);
        if (index > -1) {
            teams.splice(index, 1);
        }

        itemRandom = xoso.getRandomMegaString(teams);
        arrRange.push(itemRandom);
        index = teams.indexOf(itemRandom);
        if (index > -1) {
            teams.splice(index, 1);
        }

        itemRandom = xoso.getRandomMegaString(teams);
        arrRange.push(itemRandom);
        index = teams.indexOf(itemRandom);
        if (index > -1) {
            teams.splice(index, 1);
        }

        itemRandom = xoso.getRandomMegaString(teams);
        arrRange.push(itemRandom);
        //chuyen tat ca ket qua ve anh gif
        for (let i = 0; i < arrRange.length; i++) {
            $('#mega645_' + i).html('<i class="icon-spinner fa-spin"></i>');
        }
        //gan du lieu cho tung ket qua, moi ket qua cach nhau 2000
        for (let i = 0; i < arrRange.length; i++) {
            xoso.sethtmlMega('mega645_' + i, arrRange[i], 2000 * i);
        }
    },
    sethtmlMega: function (id, value, time) {
        setTimeout(function () { xoso.sethtmlMegaRuning(id, value); }, time);
    },
    sethtmlPower: function (id, value, time) {
        setTimeout(function () { xoso.sethtmlPowerRuning(id, value); }, time);
    },
    sethtmlMax4D: function (id, value, time) {
        setTimeout(function () { xoso.sethtmlMax4DRuning(id, value); }, time);
    },
    sethtmlMegaRuning: function (id, value) {
        let animationTimer = null;
        let started = new Date().getTime();
        let duration = 2000;
        let minNumber = 0; // le minimum
        let maxNumber = 45; // le maximum
        //$('#' + id).html('<span id="output0"></span>');
        animationTimer = setInterval(function () {
            if (new Date().getTime() - started < duration) {
                //so chay random truoc khi show ket qua
                let gt = Math.floor(Math.random() * (maxNumber - minNumber + 1) + minNumber);
                $('#' + id).text('' + parseInt(gt) < 10 ? "0" + gt : gt);
            }
            else {
                clearInterval(animationTimer); // Stop the loop
                //show ket qua
                $('#' + id).html(value);
                $('#' + id).attr("data", value);
            }
        }, 100);
    },
    sethtmlPowerRuning: function (id, value) {
        let animationTimer = null;
        let started = new Date().getTime();
        let duration = 2000;
        let minNumber = 0; // le minimum
        let maxNumber = 55; // le maximum
        //$('#' + id).html('<span id="output0"></span>');
        animationTimer = setInterval(function () {
            if (new Date().getTime() - started < duration) {
                //so chay random truoc khi show ket qua
                let gt = Math.floor(Math.random() * (maxNumber - minNumber + 1) + minNumber);
                $('#' + id).text('' + parseInt(gt) < 10 ? "0" + gt : gt);
            }
            else {
                clearInterval(animationTimer); // Stop the loop
                //show ket qua
                $('#' + id).html(value);
                $('#' + id).attr("data", value);
            }
        }, 100);
    },
    sethtmlMax4DRuning: function (id, value) {
        let animationTimer = null;
        let started = new Date().getTime();
        let duration = 2000;
        let minNumber = 0; // le minimum
        let maxNumber = 9; // le maximum
        $('#' + id).html('<span id="output0"></span>' +
            '<span id="output1"></span>' +
            '<span id="output2"></span>' +
            '<span id="output3"></span>');
        animationTimer = setInterval(function () {
            if (new Date().getTime() - started < duration) {
                //so chay random truoc khi show ket qua
                for (let i = 0; i < value.length; i++) {
                    $('#output' + i).text('' + Math.floor(Math.random() * (maxNumber - minNumber + 1) + minNumber));
                }
            }
            else {
                clearInterval(animationTimer); // Stop the loop
                //show ket qua
                $('#' + id).html(value);
                $('#' + id).attr("data", value);
                if (id == "max4d_5") {
                    $('#max4d_6').html("X" + value.substr(1));
                    $('#max4d_6').attr("data", value);
                    $('#max4d_7').html("XX" + value.substr(2));
                    $('#max4d_7').attr("data", value);
                }
            }
        }, 100);
    },
    getRandomMegaString: function (teams) {
        let gt = Math.floor(Math.random() * teams.length);
        return (parseInt(teams[gt]) < 10 ? "0" + teams[gt] : teams[gt]);
    },
    RunRandomComplete: function (str) {
        isrunning = false;
        $('#btnStartOrStop').html('Quay thử lại');
        //$('#turn').html('<span class="change-color">NHẤP QUAY THỬ LẠI</span>');
    },
    choice: function (id, num) {
        if (!isrunning) {
            mn_mt = "table-xsmb";
            if (id == 1)
                mn_mt = "table-xsmn";
            if (id == 2)
                mn_mt = "table-xsmt";
            if (id == 3)
                mn_mt = "table-tinh";
            $('#' + mn_mt + ' tbody tr td div').each(function (index, element) {
                let txt = $(element).attr("data");
                if (num == 2 || num == 3) {
                    if (txt.length > num)
                        txt = txt.substr(txt.length - num);
                }
                $(element).text(txt);
            });
        }
    },
    loadddlLotteries: function (typeltt, LotteryGroupId, LotteryCode) {
        let dayofweek = '';
        let ddlLotteries = document.getElementById("ddlProvincesQuayThu");
        $("#ddlProvincesQuayThu").empty();
        if (typeltt == 1) {
            $.ajax({
                url: xosoconfig.rootPath + 'Utils/GetAllLotteries',
                //data: {},
                type: 'GET',
                success: function (data) {
                    let objJSON = JSON.parse(data);
                    let option;
                    option = document.createElement("option");
                    option.text = 'Miền Bắc';
                    option.value = xosoconfig.rootPath + 'quay-thu-xsmb.html';
                    option.setAttribute("url", xosoconfig.rootPath + 'quay-thu-xsmb.html');
                    ddlLotteries.add(option, 0);
                    for (let i = 1; i < objJSON.length + 1; i++) {
                        let lotObj = objJSON[i - 1];
                        if (lotObj.LotteryGroupId <= 1) {
                            continue;
                        } else {
                            option = document.createElement("option");
                            option.text = lotObj.LotteryName;
                            option.value = xosoconfig.rootPath + "quay-thu-xs" + lotObj.LotteryCode.toLowerCase() + ".html";
                            option.setAttribute("url", xosoconfig.rootPath + "quay-thu-xs" + lotObj.LotteryCode.toLowerCase() + ".html");
                            if (lotObj.LotteryCode == LotteryCode.toUpperCase()) {
                                option.setAttribute("selected", "selected");
                            }
                            ddlLotteries.add(option, i);
                        }
                    }
                }
            });
        } else {
            option = document.createElement("option");
            option.text = 'Miền Bắc';
            option.value = xosoconfig.rootPath + 'quay-thu-xsmb.html';
            option.setAttribute("url", xosoconfig.rootPath + 'quay-thu-xsmb.html');
            if (LotteryGroupId == 1) {
                option.setAttribute("selected", "selected");
            }
            ddlLotteries.add(option);

            option = document.createElement("option");
            option.text = 'Miền Nam';
            option.value = xosoconfig.rootPath + 'quay-thu-xsmn.html';
            option.setAttribute("url", xosoconfig.rootPath + 'quay-thu-xsmn.html');
            if (LotteryGroupId == 2) {
                option.setAttribute("selected", "selected");
            }
            ddlLotteries.add(option);

            option = document.createElement("option");
            option.text = 'Miền Trung';
            option.value = xosoconfig.rootPath + 'quay-thu-xsmt.html';
            option.setAttribute("url", xosoconfig.rootPath + 'quay-thu-xsmt.html');
            if (LotteryGroupId == 3) {
                option.setAttribute("selected", "selected");
            }
            ddlLotteries.add(option);
        }
    },
    sethtml: function (id, value, time) {
        setTimeout(function () { xoso.sethtmlRuning(id, value); }, time);
    },
    sethtmlRuning: function (id, value) {
        let animationTimer = null;
        let started = new Date().getTime();
        let duration = 1000;
        let minNumber = 0; // le minimum
        let maxNumber = 9; // le maximum
        $('#' + id).html('<span class="output" id="output0"></span>' +
            '<span class="output" id="output1"></span>' +
            '<span class="output" id="output2"></span>' +
            '<span class="output" id="output3"></span>' +
            '<span class="output" id="output4"></span>' +
            '<span class="output" id="output5"></span>');
        animationTimer = setInterval(function () {
            if (new Date().getTime() - started < duration) {
                //so chay random truoc khi show ket qua
                for (let i = 0; i < value.length; i++) {
                    $('#output' + i).text('' + Math.floor(Math.random() * (maxNumber - minNumber + 1) + minNumber));
                }
            }
            else {
                clearInterval(animationTimer); // Stop the loop
                //show ket qua
                $('#' + id).html(value);
                $('#' + id).attr("data", value);
            }
        }, 100);
        xoso.addValueToTableLoto(value);
    },
    addValueToTableLoto: function (value) {
        if (value != null) {
            value = parseInt(value) % 100;

            let tail = value % 10;
            let head = value / 10;

            tail = parseInt(tail);
            head = parseInt(head);
            let strTail = $('#item_Tail_' + tail).html();
            let strHead = $('#item_Head_' + head).html();
            //if(strTail.length > 0)
            //{
            //    $('#item_Tail_' + tail).html(strTail + "," + head);
            //}
            //else {
            //    $('#item_Tail_' + tail).html(head);
            //}
            if (strHead.length > 0) {
                $('#item_Head_' + head).html(strHead + "," + tail);
            }
            else {
                $('#item_Head_' + head).html(tail);
            }
        }
    },
    getRandomString: function (len) {
        let number = '';
        for (let i = 0; i < len; i++) {
            number += Math.floor(Math.random() * (9 - 0 + 1) + 0);
        }
        return number;
    },
    sethtmlMN: function (id, value, time) {
        setTimeout(function () { xoso.sethtmlMNRuning(id, value); }, time);
    },
    sethtmlMNRuning: function (id, value) {
        let animationTimer = null;
        let started = new Date().getTime();
        let duration = 1000;
        let minNumber = 0; // le minimum
        let maxNumber = 9; // le maximum
        let LotteryCode = $('#' + id).attr("LotteryCode");
        $('#' + id).html('<span class="output" id="outputMN0"></span>' +
            '<span class="output" id="outputMN1"></span>' +
            '<span class="output" id="outputMN2"></span>' +
            '<span class="output" id="outputMN3"></span>' +
            '<span class="output" id="outputMN4"></span>');

        animationTimer = setInterval(function () {
            if (new Date().getTime() - started < duration) {
                //so chay random truoc khi show ket qua
                for (let i = 0; i < value.length; i++) {
                    $('#outputMN' + i).text('' + Math.floor(Math.random() * (maxNumber - minNumber + 1) + minNumber));
                }
            }
            else {
                clearInterval(animationTimer); // Stop the loop
                //show ket qua
                $('#' + id).html(value);
                $('#' + id).attr("data", value);

            }

        }, 100);
        xoso.XSMNaddValueToTableLoto(value, LotteryCode);
    },
    XSMNaddValueToTableLoto: function (value, LotteryCode) {
        if (value != null) {
            value = parseInt(value) % 100;
            let tail = value % 10;
            let head = value / 10;
            tail = parseInt(tail);
            head = parseInt(head);
            let strHead = $('#item_Head_' + LotteryCode + "_" + head).html();
            if (strHead.length > 0) {
                $('#item_Head_' + LotteryCode + "_" + head).html(strHead + "," + tail);
            }
            else {
                $('#item_Head_' + LotteryCode + "_" + head).html(tail);
            }
        }
    },
    goToByScroll: function (id) {
        // Remove "link" from the ID
        id = id.replace("link", "");
        // Scroll
        $('html,body').animate({ scrollTop: $("#" + id).offset().top }, 2000);

    },
    getTinhtheoNgay: function (str) {
        let url = xosoconfig.rootPath + 'tinh-theo-ngay-ajax.html';
        let dataGetter = {
            'strDay': str,
            'lotteriesId': 0
        };
        $.xosoAjax(url, 'Get', dataGetter, function (resp) {
            $("#tinhheader").html(resp);
        });
    },
    getCaptcha: function (id) {
        let captchaUrl = '';
        let uuid = this.generateUUID();
        switch (id) {
            case 1:
                {
                    captchaUrl = xosoconfig.rootPath + 'xsdp-captcha.html?id=' + uuid;
                    break;
                }
            case 2:
                {
                    captchaUrl = xosoconfig.rootPath + 'register-captcha.html?id=' + uuid;
                    break;
                }
            default:
                {
                    captchaUrl = xosoconfig.rootPath + 'retrivepassword-captcha.html?id=' + uuid;
                    break;
                }
        }
        $('#CaptchaImg' + id).attr('src', captchaUrl);
        return false;
    },
    generateUUID: function () {
        let d = new Date().getTime();
        if (window.performance && typeof window.performance.now === "function") {
            d += performance.now();
        }
        let uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            let r = (d + Math.random() * 16) % 16 | 0;
            d = Math.floor(d / 16);
            return (c == 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
        return uuid;
    },
    loteryloadmore: function (url) {
        let dataGetter = {
            'pageIndex': xoso.variables.currentPage
        };
        let lotterymoreResult = $('.loadmoreResult');
        $.xosoAjax(url, 'Get', dataGetter, function (resp) {
            if (resp.length > 0) {
                xoso.variables.currentPage++;
                lotterymoreResult.append(resp);
            } else {
                $('.btn-viewmore').hide();
            }
        });
    },
    SoKetQua: {
        variables: {
            page: 1
        },
        loteryloadmore: function () {
            let url = xosoconfig.rootPath + 'XSDPAjax/AjaxLoadMoreSoKetQua';
            let lotteryId = $("#ddLotteries option:selected").val();
            let lotteryName = $("#ddLotteries option:selected").text();
            let rollingNumber = $("#ddlRollingNumbers").val();
            if (rollingNumber == 0) { rollingNumber = 30; }
            let dataGetter = {
                'lotteryId': lotteryId,
                'rollNumber': rollingNumber,
                'pageIndex': xoso.SoKetQua.variables.page,
            };
            let Result = $('#ajaxcontent');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    xoso.SoKetQua.variables.page++;
                    Result.append(resp);
                } else {
                    $('.btn-readmore').hide();
                }
            });
        },
        loteryload: function () {
            let url = xosoconfig.rootPath + 'XSDPAjax/AjaxLoadMoreSoKetQua';
            let lotteryId = $("#ddLotteries option:selected").val();
            let lotteryName = $("#ddLotteries option:selected").text();
            let rollingNumber = $("#ddlRollingNumbers").val();
            if (rollingNumber == 0) { rollingNumber = 30; }
            let dataGetter = {
                'lotteryId': lotteryId,
                'rollNumber': rollingNumber,
                'pageIndex': 0,
            };
            let Result = $('#ajaxcontent');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    xoso.SoKetQua.variables.page = 1;
                    Result.html(resp);
                    $("#titlemain").html('Sổ kết quả xổ số ' + lotteryName);
                } else {
                    $('.btn-readmore').hide();
                }
            });
        },
    },
    Xsmb: {
        variables: {
            page: 1
        },
        loadmore: function () {
            let url = xosoconfig.rootPath + 'Xsmb/MobiGetMore';
            let dataGetter = {
                'pageIndex': xoso.Xsmb.variables.page,
            };
            let lotteryMbResult = $('#ajaxContentContainer');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    xoso.Xsmb.variables.page++;
                    lotteryMbResult.append(resp);
                } else {
                    $('#loadmore').hide();
                }
            });
        },
        loadmorebydayofweek: function (dayofWeek) {
            let url = xosoconfig.rootPath + 'Xsmb/MobiGetMoreMBTheoThu';
            let dataGetter = {
                'pageIndex': xoso.Xsmb.variables.page,
                'dayOfWeek': dayofWeek
            };
            let lotteryMbResult = $('#ajaxContentContainer');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    xoso.Xsmb.variables.page++;
                    lotteryMbResult.append(resp);
                } else {
                    $('#loadmore').hide();
                }
            });
        },
        loadLotteryMoreByDayOfWeekMobi: function (url, DayOfWeek) {
            let dataGetter = { 'pageIndex': pageIndex, 'dayOfWeek': DayOfWeek };
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    pageIndex++;
                    $('#ajaxContentContainer').append(resp);
                } else {
                    $('#xemthem').hide();
                }
            });
        }
    },
    Xsmt: {
        variables: {
            page: 1
        },
        loadmore: function () {
            let url = xosoconfig.rootPath + 'Xsmt/MobiGetMore';
            let dataGetter = {
                'pageIndex': xoso.Xsmt.variables.page
            };
            let lotteryMNResult = $('#ajaxContentContainer');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    xoso.Xsmt.variables.page++;
                    lotteryMNResult.append(resp);
                } else {
                    $('#loadmore').hide();
                }
            });
        },
        loadmorebydayofweek: function (dayofWeek) {
            let url = xosoconfig.rootPath + 'Xsmt/MobiGetMoreByDayOfWeek';
            let dataGetter = {
                'pageIndex': xoso.Xsmt.variables.page,
                'dayOfWeek': dayofWeek
            };
            let lotteryMTResult = $('#ajaxContentContainer');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    xoso.Xsmt.variables.page++;
                    lotteryMTResult.append(resp);
                } else {
                    $('#loadmore').hide();
                }
            });
        }
    },
    Xsmn: {
        variables: {
            page: 1
        },
        loadmore: function () {
            let url = xosoconfig.rootPath + 'Xsmn/MobiGetMore';
            let dataGetter = {
                'pageIndex': xoso.Xsmn.variables.page
            };
            let lotteryMNResult = $('#ajaxContentContainer');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    xoso.Xsmn.variables.page++;
                    lotteryMNResult.append(resp);
                } else {
                    $('#loadmore').hide();
                }
            });
        },
        loadmorebydayofweek: function (dayofWeek) {
            let url = xosoconfig.rootPath + 'Xsmn/MobiGetMoreByDayOfWeek';
            let dataGetter = {
                'pageIndex': xoso.Xsmn.variables.page,
                'dayOfWeek': dayofWeek
            };
            let lotteryMnResult = $('#ajaxContentContainer');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    xoso.Xsmn.variables.page++;
                    lotteryMnResult.append(resp);
                } else {
                    $('#loadmore').hide();
                }
            });
        }
    },
    Xsmnbydayofweek: {
        variables: {
            page: 1
        },
        loterytheothuloadmore: function (url, dayofWeek) {
            let dataGetter = {
                'pageIndex': xoso.Xsmnbydayofweek.variables.page,
                'dayofWeek': dayofWeek
            };
            let lotteryMbResult = $('.lotteryMNByDayOfWeekResult');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    xoso.Xsmnbydayofweek.variables.page++;
                    lotteryMbResult.append(resp);
                } else {
                    $('.btn-viewmore').hide();
                }
            });
        }
    },
    KQDai: {
        variables: {
            page: 1
        },
        loteryloadmore: function (url, lotteryId) {
            let dataGetter = {
                'lotteryId': lotteryId,
                'pageIndex': xoso.KQDai.variables.page
            };
            let lotteryMbResult = $('.lotteryResult');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    xoso.KQDai.variables.page++;
                    lotteryMbResult.append(resp);
                } else {
                    $('.btn-viewmore').hide();
                }
            });
        }
    },
    dayOfWeekChange: function (dayOfWeek, lotteryGroupId) {
        let lotteries = [];
        let ddLotteries = $('#ddLotteries');
        let lotteryGroupIdValid = typeof lotteryGroupId != 'undefined' && $.isNumeric(lotteryGroupId);
        $.xosoAjax('/Utils/GetLotteriesByDayOfWeek', 'Get', { 'dayOfWeek': dayOfWeek }, function (resp) {
            lotteries = [];
            let objJson = JSON.parse(resp);
            if (!lotteryGroupIdValid)
                ddLotteries.html($('<option></option>').val(0).attr('data-id', 0).html('Miền Bắc'));
            else ddLotteries.html('');
            //ddLotteries.append($('<option>', {
            //    value: 2,
            //    text: 'Miền Trung'
            //})).append($('<option>', {
            //    value: 3,
            //    text: 'Miền Nam'
            //}));
            $.each(objJson,
                function (i, option) {
                    if (lotteryGroupIdValid) {
                        if (option.LotteryGroupId == lotteryGroupId) {
                            lotteries.push(option);
                            ddLotteries.append($('<option></option>').val(option.LotteryId).attr('data-id', option.LotteryGroupId).attr('data-code', option.LotteryCode).html(option.LotteryName));
                        }
                    }
                    else if (option.LotteryGroupId > 1) {
                        lotteries.push(option);
                        ddLotteries.append($('<option></option>').val(option.LotteryId).attr('data-id', option.LotteryGroupId).attr('data-code', option.LotteryCode).html(option.LotteryName));
                    }
                });
        });
    },
    ThongKe: {
        GroupChange: function () {
            $("#ddLotteries").html('');
            let ddlGroups = $("#ddlGroups");
            let ddlLotteries = $("#ddLotteries");
            let groupId = $("#ddlGroups option:selected").val();
            let lotteryId = $("#ddLotteries option:selected").val();
            if (groupId <= 1) {
                ddlLotteries.append($('<option></option>').val(0).html('Miền Bắc'));
            } else {
                $.ajax({
                    url: xosoconfig.rootPath + 'Utils/GetAllLotteries',
                    type: 'Get',
                    success: function (data) {
                        let objJson = JSON.parse(data);
                        $.each(objJson, function (i, option) {
                            if (option.LotteryGroupId == groupId) {
                                ddlLotteries.append($('<option></option>').val(option.LotteryId).html(option.LotteryName));
                            }
                        });
                    }
                });
            }
        },
        getHomeReport: function () {
            let url = xosoconfig.rootPath + 'XSDPAjax/GetHomeReport';
            let lotteryId = $("#ddLotteries option:selected").val();
            let lotteryName = $("#ddLotteries option:selected").text();
            let lotteryCode = $("#ddLotteries option:selected").attr('tag');
            let rollingNumber = $("#ddlRollingNumbers").val();
            if (rollingNumber == 0) { rollingNumber = 30; }
            let dataGetter = {
                'lotteryId': lotteryId,
                'TimeRolled': rollingNumber
            };
            let Result = $('#HomeResult');
            Result.html("");
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    Result.html(resp);
                }
                $("#titlemain").html('Thống kê kết quả 2 số cuối đặc biệt XS' + lotteryCode + ' - xổ số ' + lotteryName);
            });
        },
        getThongKeTheoTanSuat: function () {
            let url = xosoconfig.rootPath + 'XSDPAjax/AjaxGetTanSuatDB';
            let lotteryId = $("#ddLotteries option:selected").val();
            let lotteryName = $("#ddLotteries option:selected").text();
            let rollingNumber = $("#ddlRollingNumbers").val();
            let dateView = $("input:text[name=date]").val();
            let type = $("input[name=TanSuatType]:checked").val();
            let lotteryCode = $("#ddLotteries option:selected").attr('tag');
            if (rollingNumber == 0) { rollingNumber = 30; }
            let dataGetter = {
                'lotteryId': lotteryId,
                'rollNumber': rollingNumber,
                'dateview': dateView,
                'type': type
            };
            let Result = $('#content');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    Result.html(resp);
                }
                $('.title-main').html("Bảng thống kê tần suất giải đặc biệt XS" + lotteryCode);
            });
        },
        lokepResult: function () {
            let url = xosoconfig.rootPath + 'ThongKeAjax/ThongKeLoKep';
            let lotteryId = $('#ddLotteries option:selected').val(),
                lotteryName = $('#ddLotteries option:selected').text(),
                lotteryCode = $('#ddLotteries option:selected').data('code'),
                dayOfWeek = $('#ddlDayOfWeeks option:selected').text(),
                rollingNumber = $('#ddlRollingNumbers option:selected').val(),
                lotteryGroupId = $('#ddLotteries option:selected').data('id');
            let dataGetter = {
                'lotteryId': lotteryId,
                'lotteryGroupId': lotteryGroupId,
                'lotteryName': lotteryName,
                'lotteryCode': lotteryCode,
                'rollingNumber': rollingNumber,
                'dayOfWeek': dayOfWeek
            };
            let Result = $('#ajaxContentContainer');
            let title = $('#titlelokep');
            title.html = 'Lô kép ' + lotteryCode + ' - Thống kê Lô kép ' + lotteryName + ' Chuẩn‌';
            $.xosoAjax(url, 'POST', dataGetter, function (resp) {
                if (resp.length > 0) {
                    Result.html(resp);
                } else {
                    $('.btn-readmore').hide();
                }

            });
        },
        giaiDacBietHome: {
            getReport: function () {
                let lotteryId = $("#ddLotteries option:selected").val();
                let DateFrom = $("#txtDateFrom1").val();
                let DateTo = $("#txtDateTo1").val();
                let url = xosoconfig.rootPath + 'ThongKeAjax/ThongKeGiaiDacBietByDate';
                let dataGetter = {
                    'lotteryId': lotteryId,
                    'dateFrom': DateFrom,
                    'dateTo': DateTo,
                };
                let tkgiaiDB = $('#tkgiaiDB');
                $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                    if (resp.length > 0) {
                        tkgiaiDB.html(resp);
                    }
                });
            }
        },
        giaiDacBiet: {
            getReport: function () {
                let lotteryId = $("#ddLotteries option:selected").val();
                let lotteryName = $("#ddLotteries option:selected").text();
                let rowAmount = $("#amplitude").val();
                let url = xosoconfig.rootPath + 'thong-ke-giai-db-ajax.html';
                let dataGetter = {
                    'lotteryId': lotteryId,
                    'lotteryName': lotteryName,
                    'pageIndex': 0,
                    'rowAmount': rowAmount
                };
                let tkgiaiDB = $('#tkgiaiDB');
                $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                    if (resp.length > 0) {
                        tkgiaiDB.html(resp);
                    }
                });
            }
        },
        TKLoKep: {
            getReport: function () {
                let lotteryId = $('#ddLotteries option:selected').val(),
                    lotteryName = $('#ddLotteries option:selected').text(),
                    lotteryCode = $('#ddLotteries option:selected').data('code'),
                    dayOfWeek = $('#ddlDayOfWeeks option:selected').text(),
                    rollingNumber = $('#ddlRollingNumbers option:selected').val(),
                    lotteryGroupId = $('#ddLotteries option:selected').data('id');
                let loading = document.getElementById('loadmore');
                loading.value = "Đang tải dữ liệu...";
                $.xosoAjax('/ThongKeAjax/ThongKeLoKep',
                    'post',
                    {
                        'lotteryId': lotteryId,
                        'lotteryGroupId': lotteryGroupId,
                        'lotteryName': lotteryName,
                        'lotteryCode': lotteryCode,
                        'rollingNumber': rollingNumber,
                        'dayOfWeek': dayOfWeek
                    },
                    function (resp) {
                        if (resp.length > 0) {
                            $('#ajaxContentContainer').html(resp);
                        }
                    });
                loading.value = "Kết quả";
            }
        },
        DayOfWeekChange: function () {
            let ddLotteries = $("#ddLotteries");
            let dayOfWeek = $("#ddlDayOfWeeks option:selected").val();
            let url = xosoconfig.rootPath + 'Utils/GetLotteriesByDayOfWeek';
            let dataGetter = {
                'dayOfWeek': dayOfWeek
            };
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                let objJSON = JSON.parse(resp);
                ddLotteries.html($('<option></option>').val(0).html('Miền Bắc'));
                $.each(objJSON, function (i, option) {
                    if (option.LotteryGroupId > 1) ddLotteries.append($('<option></option>').val(option.LotteryId).html(option.LotteryName).attr('tag', option.LotteryCode));
                });
            });
        },
        TKDauDuoiDayOfWeekChange: function () {
            let ddLotteries = $("#ddLotteries");
            let dayOfWeek = $("#ddlDayOfWeeks option:selected").val();
            ddLotteries.html($('<option></option>').val("0@@1").html('Miền Bắc'));
            let url = xosoconfig.rootPath + 'Utils/GetLotteriesByDayOfWeek';
            let dataGetter = {
                'dayOfWeek': dayOfWeek
            };
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                let objJSON = JSON.parse(resp);
                $.each(objJSON, function (i, option) {
                    if (option.LotteryGroupId > 1) ddLotteries.append($('<option></option>').val(option.LotteryId + "@@" + option.LotteryGroupId).html(option.LotteryName));
                });
            });
        },
        MNMTDayOfWeekChange: function (lotteryGroupId) {
            let ddLotteries = $("#ddLotteries");
            let dayOfWeek = $("#ddlMNDayOfWeeks option:selected").val();
            let url = xosoconfig.rootPath + 'Utils/GetLotteriesByDayOfWeek';
            let dataGetter = {
                'dayOfWeek': dayOfWeek
            };
            ddLotteries.html('');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                let objJSON = JSON.parse(resp);
                $.each(objJSON, function (i, option) {
                    if (option.LotteryGroupId == lotteryGroupId) {
                        ddLotteries.append($('<option></option>').val(option.LotteryId).html(option.LotteryName));
                    }
                });
            });
        },
        MNMTDayOfWeekChangeLotteryCode: function (lotteryGroupId) {
            let ddLotteries = $("#ddLotteries");
            let dayOfWeek = $("#ddlMNDayOfWeeks option:selected").val();
            let url = xosoconfig.rootPath + 'Utils/GetLotteriesByDayOfWeek';
            let dataGetter = {
                'dayOfWeek': dayOfWeek
            };
            ddLotteries.html('');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                let objJSON = JSON.parse(resp);
                $.each(objJSON, function (i, option) {
                    if (option.LotteryGroupId == lotteryGroupId) {
                        ddLotteries.append($('<option></option>').val(option.LotteryCode.toLowerCase()).html(option.LotteryName));
                    }
                });
            });
        },
        thongKeCauTheoTinh: function () {
            let lotteryCode = $("#ddLotteries option:selected").val();
            window.location = '/tham-khao-xo-so/soi-cau-xo-so-' + lotteryCode + '.html';
        },
        Thongke0099: function () {
            $("#ajaxContentContainer").html('&nbsp;&nbsp;<img src="/public/images/load/Spinner.svg"/> Đang tải dữ liệu...');
            let lotteryId = $("#ddLotteries").val();
            let lotteryName = $("#ddLotteries option:selected").text();
            let rollNumber = $("#ddlTimeRooled").val();
            let url = xosoconfig.rootPath + 'ThongKeAjax/ThongKe0099';
            let dataGetter = {
                'lotteryId': lotteryId,
                'lotteryName': lotteryName,
                'rollNumber': rollNumber
            };
            let Tk0099 = $('#ajaxContentContainer');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    Tk0099.html(resp);
                }
            });
        }
    },
    articles: {
        variables: {
            page: 1
        },
        loadmore: function (url, cateId) {
            let dataGetter = {
                'catId': cateId,
                'pageIndex': xoso.articles.variables.page
            };
            let lotterymoreResult = $('.loadmoreResult');
            $.xosoAjax(url, 'Get', dataGetter, function (resp) {
                if (resp.length > 0) {
                    xoso.articles.variables.page++;
                    lotterymoreResult.append(resp);
                } else {
                    $('.btn-viewmore').hide();
                }
            });
        }
    },
    setPercent: function () {
        let progress = $('.progress').length;
        for (let i = 1; i <= progress; i++) {
            let percent = parseInt($('#percent-' + i).data('percent'));
            $('#progress-percent-' + i).width(percent + '%');
        }
    },
    scrollToElement: function (selector, callback) {
        let animation = {
            scrollTop: $(selector).offset().top
        };
        $('html,body').animate(animation, 'slow', 'swing', function () {
            if (typeof callback == 'function') {
                callback();
            }
            callback = null;
        });
    },
    clickScroll: function (kq_id) {
        //window.setTimeout(function () {
        //    xoso.scrollToElement("#" + kq_id);
        //}, 1000);
        let scroll = "#" + kq_id;
        $('html,body').animate(
            {
                scrollTop: $(scroll).offset().top
            }, 1000);
    },
    dialog: function (element, title, text, width, height, onClose) {
        let xosoDialog = element == null ? $('<div><p style="padding:15px;">' + (text == null ? '' : text) + '</p></div>') : $('#' + element);
        xosoDialog.dialog({
            title: title == null ? 'Thông báo' : title,
            width: width == null ? 300 : width,
            height: height == null ? 200 : height,
            resizable: false,
            autoOpen: true,
            modal: true,
            responsive: true,
            buttons: {
                "Đóng": function () {
                    $(this).dialog("close");
                }
            },
            close: typeof (onClose) == "function" ? onClose : function () { }
        });
    }
}

function showAlertDangQuay() {
    try {
        let xsvietlott = '';
        switch (xoso.RunningQuayThuVietlott) {
            case 3:
                xsvietlott = 'xổ số Mega 6/45';
                break;
            case 1:
                xsvietlott = 'xổ số Power 6/55';
                break;
            case 2:
                xsvietlott = 'xổ số Max4D';
                break;

        }
        if (xsvietlott.length) {
            let msg = "Đang quay thử " + xsvietlott;
            toastr.warning(msg);
        }
    } catch (e) {
    }
}
function startOrStopMega645() {
    if (xoso.RunningQuayThuVietlott > 0 && xoso.RunningQuayThuVietlott != 3) {
        showAlertDangQuay();
    } else {
        let command = document.getElementById('btnStartOrStop_Mega645').innerHTML;
        if (command.toString().includes('Đang quay thử') == false) {
            xoso.RunRandomMega();
            setTimeout(function () {
                xoso.RunRandomMegaComplete();
            }, 12000);
        }
    }
}
function startOrStopPower655() {
    if (xoso.RunningQuayThuVietlott > 0 && xoso.RunningQuayThuVietlott != 1) {
        showAlertDangQuay();
    } else {
        let command = document.getElementById('btnStartOrStop_Power655').innerHTML;
        if (command.toString().includes('Đang quay thử') == false) {
            xoso.RunRandomPower();
            setTimeout(function () {
                xoso.RunRandomPowerComplete();
            }, 14000);
        }
    }
}
function startOrStopMax4D() {
    if (xoso.RunningQuayThuVietlott > 0 && xoso.RunningQuayThuVietlott != 2) {
        showAlertDangQuay();
    } else {
        let command = document.getElementById('btnStartOrStop_Max4D').innerHTML;
        if (command.toString().includes('Đang quay thử') == false) {
            xoso.RunRandomMax4D();
            setTimeout(function () {
                xoso.RunRandomMax4DComplete();
            }, 16000);
        }
    }
}

let $dateEnd = $('#dateEnd');
let $dateBegin = $('#dateBegin');
$dateEnd.datepicker({
    format: 'dd/mm/yyyy',
    endDate: '+0d',
    startDate: '-1m'
}).on('change', function () {
    let date = $dateEnd.datepicker('getDate');
    $dateBegin.datepicker('setEndDate', date);
});
$dateBegin.datepicker({
    format: 'dd/mm/yyyy',
    endDate: '+0d',
}).on('change', function () {
    let date = $dateBegin.datepicker('getDate');
    $dateEnd.datepicker('setStartDate', date);
});

$('#datepicker').datepicker({
    format: 'dd-mm-yyyy',
    todayHighlight: true,
    endDate: '+0d',
    startDate: new Date(2009, 0, 1),
    autoclose: true,
    weekStart: 1,
    templates: {
        leftArrow: '<i class="icon-chevron-left"></i>',
        rightArrow: '<i class="icon-chevron-right"></i>'
    }
}).on('changeDate', function (e) {
    $(".today.day").closest('tr').addClass('currentWeek');
    let dateUtc = $('#datepicker').datepicker('getDate');
    let value = $('#datepicker').datepicker('getFormattedDate');
    let timeStamp = new Date(dateUtc).getTime();
    let now = new Date();
    if (timeStamp < now.getTime()) {
        window.location.href = base_url + 'kqxs-ngay-' + value + '.html';
    } else {
        toastr.warning('Ngày ' + value + ' chưa mở thưởng');
    }
});

function copyToClipboard(copyText) {
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val(copyText).select();
    document.execCommand("copy");
    $temp.remove();
    alert('Đã copy dàn xong!');
}

/*contact*/
$('.btn-contact').click(function () {
    let contact_name = $('input[name="contact_name"]').val();
    let contact_title = $('input[name="contact_title"]').val();
    let contact_content = $('textarea[name="contact_content"]').val();
    if (!contact_name) {
        alert('Bạn cần nhập Tên liên hệ');
        return;
    }
    if (!contact_title) {
        alert('Bạn cần nhập Tiêu đề yêu cầu');
        return;
    }
    if (!contact_content) {
        alert('Bạn cần nhập Nội dung');
        return;
    }
    $.ajax({
        url: '',
        type: 'POST',
        data: {
            contact_name: contact_name,
            contact_title: contact_title,
            contact_content: contact_content,
        },
        success: function (data) {
            if (data) {
                alert('Cảm ơn bạn đã gửi yêu cầu. Chúng tôi sẽ phản hồi trong thời gian sớm nhất!');
                location.reload();
            }
        }
    });
})
/*end contact*/

/*
$('.input-search-menu').on('keypress',function(e) {
    if(e.which == 13) {
        let keyword = $(this).val();
        window.location.href = "/tim-kiem?s=" + keyword;
    }
});*/

$('.chat_login').click(function () {
    var username = $('.form_login input[name=username]').val();
    var password = $('.form_login input[name=password]').val();
    var session_chat = $('.comment input[name=session_chat]');
    if (username === '' || password === '') {
        toastr.warning('Vui lòng nhập username/password!');
    } else {
        $.ajax({
            url: 'chat/login',
            type: 'POST',
            data: {
                username: username,
                password: password
            },
            success: function (data) {
                if (data) {
                    toastr.success('Đăng nhập thành công!');
                    data = JSON.parse(data);
                    session_chat.attr('data-username', username).val(data.id);
                    $('#login_modal').modal('hide');
                    $('.comment input[name=content_chat]').removeAttr('readonly');
                    $('.comment .cmt-avata img').attr('src', data.avatar);
                    if (data.role === 'admin') window.location.reload();
                } else {
                    toastr.warning('Sai username/password!');
                }
            }
        });
    }
});

window.onload = function () {
    if (typeof $('.box-comment .ajax-content')[0] != 'undefined') {
        $('.box-comment .ajax-content').scrollTop($('.box-comment .ajax-content')[0].scrollHeight);
        $('[data-toggle="popover"]').popover();
    }
}

$('.chat_signup').click(function () {
    var username = $('.form_signup input[name=username]').val();
    var password = $('.form_signup input[name=password]').val();
    var email = $('.form_signup input[name=email]').val();
    var fd = new FormData();
    var avatar = $('.form_signup input[name=avatar]')[0].files;
    fd.append('file', avatar[0]);
    fd.append('username', username);
    fd.append('password', password);
    fd.append('email', email);
    if (username === '' || password === '') {
        toastr.warning('Vui lòng nhập username/password!');
    } else {
        $.ajax({
            url: 'chat/signup',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (data) {
                if (data) {
                    toastr.success('Đăng ký thành công!');
                    $('#signup_modal').modal('hide');
                    $('#login_modal').modal('show');
                } else {
                    toastr.warning('Username đã được sử dụng!');
                }
            }
        });
    }
});

$('.send_chat').on('click', function () {
    var content = $('.comment input[name=content_chat]');
    var avatar = $('.comment .cmt-avata').html();
    var session_chat = $('.comment input[name=session_chat]');
    var username = session_chat.data('username');
    var user_id = session_chat.val();
    if (content.val() !== '') {
        $.ajax({
            url: 'chat/add_chat',
            type: 'POST',
            data: {
                user_id: user_id,
                content: content.val()
            },
            success: function (data) {
                if (data) {
                    var row_chat = '<div class="commented my-3">\n' +
                        '<span class="cmt-avata">\n' +
                        avatar +
                        '    </span>\n' +
                        '    <div>\n' +
                        '   <span class="cmt-tag">@' + username + '</span>\n' +
                        '    <span class="content">' + content.val() + '</span>\n' +
                        '    <small class="text-gray1"><i>' + data + '</i></small>' +
                        '    </div>\n' +
                        '   </div>';
                    content.val('');
                    $('.box-comment .ajax-content').append(row_chat);
                    $('.box-comment .ajax-content').scrollTop($('.box-comment .ajax-content')[0].scrollHeight);
                }
            }
        });
    }
})

$('.delete_chat').on('click', function (e) {
    e.preventDefault();
    var id = $(this).attr('data-id');
    $('#confirm_delete').attr('data-id', id).modal('show');
    chat_delete();
})

$('.comment input[name=content_chat]').focus(function () {
    var session_chat = $('.comment input[name=session_chat]');
    if (session_chat.val() === '') {
        $('#login_modal').modal('show');
        $('.comment input[name=content_chat]').attr('readonly', 'true');
    }
})

$('.form_signup input[name=avatar]').change(function () {
    const [file] = $('.form_signup input[name=avatar]')[0].files;
    if (file) {
        $('.avatar_preview').attr('src', URL.createObjectURL(file))
    }
})

function detectProvinceByDow(changed = 0) {
    let dow = $("#detectProvinceByDow option:selected").val();
    $('#selectProvince option').addClass('d-none');
    $('#selectProvince option[data-prize*="' + dow + '"]').removeAttr('class');
    if (changed) {
        $('#selectProvince option').removeAttr('selected');
        $('#selectProvince option:not([class]):nth(0)').prop('selected', true);
    }

    $("#detectProvinceByDow").change(function () {
        detectProvinceByDow(1);
    });
}
function postFormByProvince() {
    if (typeof turnOnPostFormByProvince === 'undefined')
        return;
    $('#selectProvince').change(function () {
        let urlChaneg = $('#selectProvince option:selected').val();
        $(this).closest('form').prop('action', urlChaneg).submit();
    });
}
function chat_delete() {
    $('.chat_confirm_delete').click(function () {
        var id = $('#confirm_delete').attr('data-id');
        $.ajax({
            url: 'chat/delete_chat',
            type: 'POST',
            data: {
                id: id
            },
            success: function (data) {
                if (data) {
                    $(".box-comment .commented[data-id=" + id + "]").remove();
                    $('#confirm_delete').removeAttr('data-id').modal('hide');
                }
            }
        });
    })
}
function chotso_rbk() {
    $(document).on('click', '#chotso_rbk', function () {
        let btn = $(this);
        let countDown = $('#countDown');
        let result = $('#showResult');

        btn.remove();
        countDown.parent().removeClass('d-none');
        let numCount = 29;
        let e = setInterval(function () {
            countDown.html(('0' + numCount).substr(-2));
            numCount--;

            if (numCount < 0) {
                clearInterval(e);
                countDown.parent().remove();
                let numberRandom = Math.random(0, 99);
                numberRandom = '0' + numberRandom;
                result.html(numberRandom.substr(-2));
                result.parent().removeClass('d-none');
            }
        }, 1000);
    })
}
$(document).on('click', '.view-lucky-number', function () {
    let year = parseInt($('.sl-year').val());
    let gender = parseInt($('input[name="rd-gender"]:checked').val());
    let result = lucky_number[year][gender];
    let gender_text;
    if (gender === 0) gender_text = 'Nam';
    else gender_text = 'Nữ';
    $('.lucky-number-gender').html(gender_text);
    $('.lucky-number-year').html(year);
    let arr_result = result.split('-');
    $('.result-lucky-number-1').html(arr_result[0]);
    $('.result-lucky-number-2').html(arr_result[1]);
    $('.result-lucky-number-3').html(arr_result[2]);
    $('.result-lucky-number').removeClass('d-none');
});
function SoKetQua(option) {
    let selector = $("#lichSoKetQua");
    selector.datepicker(option).on('changeDate', function () {
        let code = selector.data('code').toLowerCase();
        let value = selector.datepicker('getFormattedDate');
        window.location.href = base_url + code + '-' + value + '.html';
    });
}
$.fn.datepicker.dates['vi'] = {
    days: ["Chủ Nhật", "Thứ 2", "Thứ 3", "Thứ 4", "Thứ 5", "Thứ 6", "Thứ 7", "Chủ Nhật"],
    daysShort: ["CN", "T2", "T3", "T4", "T5", "T6", "T7", "CN"],
    daysMin: ["CN", "T2", "T3", "T4", "T5", "T6", "T7", "CN"],
    months: ["Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6", "Tháng 7", "Tháng 8", "Tháng 9", "Tháng 10", "Tháng 11", "Tháng 12"],
    monthsShort: ["Th.1", "Th.2", "Th.3", "Th.4", "Th.5", "Th.6", "Th.7", "Th.8", "Th.9", "Th.10", "Th.11", "Th.12"],
    today: "Hôm nay"
};