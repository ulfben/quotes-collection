/* Modified for ulfben's fork on 2026-10-20; see the changelog in readme.txt. */
var quotescollectionInstances = Object.create(null);

function quotescollectionNavigation(args) {
	var navigation = jQuery('<div class="navigation"><div class="nav-next"></div></div>');
	jQuery('<button type="button" class="next-quote-link"></button>')
		.text(quotescollectionHtmlDecode(quotescollectionAjax.nextQuote))
		.on('click', function () { quotescollectionRefresh(args); })
		.appendTo(navigation.find('.nav-next'));
	return navigation;
}

function quotescollectionRefresh(args) {
	var wrapper = jQuery(document.getElementById(args.instanceID));
	if (!wrapper.length || args.loading) { return; }
	args.loading = true;
	wrapper.attr('aria-busy', 'true');
	wrapper.find('.next-quote-link').prop('disabled', true).text(quotescollectionAjax.loading);
	jQuery.ajax({
		type: 'POST', url: quotescollectionAjax.ajaxUrl, dataType: 'json',
		data: {
			action: 'quotescollection', _ajax_nonce: quotescollectionAjax.nonce,
			current: args.currQuoteID, char_limit: args.charLimit, tags: args.tags, orderby: args.orderBy
		}
	}).done(function (response) {
		if (!response || !response.quote_id) { return; }
		args.dynamicFetch = 0;
		args.currQuoteID = response.quote_id;
		wrapper.html(quotescollectionDisplayFormat(response, args));
	}).always(function () {
		args.loading = false;
		wrapper.attr('aria-busy', 'false');
		wrapper.find('.navigation').remove();
		if (args.ajaxRefresh && !args.autoRefresh) { wrapper.append(quotescollectionNavigation(args)); }
		if (args.ajaxRefresh && args.autoRefresh) { quotescollectionTimer(args); }
	});
}

function quotescollectionDisplayFormat(quoteData, args) {
	var display = '<p>' + quoteData.quote + '</p>';
	var attribution = '';
	// Quote HTML and wrapper HTML are filtered with wp_kses_post on the server.
	if (args.showAuthor && quoteData.author) { attribution = '<cite class="author">' + quoteData.author + '</cite>'; }
	if (args.showSource && quoteData.source) {
		if (attribution) { attribution += ', '; }
		attribution += '<cite class="source title">' + quoteData.source + '</cite>';
	}
	if (attribution) { display += args.beforeAttribution + attribution + args.afterAttribution; }
	return args.before + display + args.after;
}

function quotescollectionRefreshInstance(instanceID) {
	if (quotescollectionInstances[instanceID]) { quotescollectionRefresh(quotescollectionInstances[instanceID]); }
}

function quotescollectionTimer(args) {
	var maximum = Number(quotescollectionAjax.autoRefreshMax);
	args.refreshCount = args.refreshCount || 0;
	if (!maximum || args.refreshCount < maximum) {
		args.refreshCount++;
		setTimeout(function () { quotescollectionRefresh(args); }, Math.max(3, Number(args.autoRefresh)) * 1000);
	}
}

function quotescollectionHtmlDecode(input) {
	return new DOMParser().parseFromString(input, 'text/html').documentElement.textContent;
}

jQuery(function () {
	jQuery('[data-quotescollection]').each(function () {
		var args = JSON.parse(this.getAttribute('data-quotescollection'));
		quotescollectionInstances[args.instanceID] = args;
		if (args.dynamicFetch) { quotescollectionRefresh(args); }
		else if (args.ajaxRefresh && args.autoRefresh) { quotescollectionTimer(args); }
		else if (args.ajaxRefresh) { jQuery(this).append(quotescollectionNavigation(args)); }
	});
});
