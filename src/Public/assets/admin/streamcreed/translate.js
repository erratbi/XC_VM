(function () {
    'use strict';
    var translations = window.streamcreedTranslations || {};
    if (!Object.keys(translations).length) return;

    function translate(value) {
        var match = String(value).match(/^(\s*)([\s\S]*?)(\s*)$/), translated = translations[match[2]];
        if (translated !== undefined) return match[1] + translated + match[3];
        if (document.documentElement.lang === 'fr') {
            var page = match[2].match(/^Page (\d+) of (\d+)$/);
            if (page) return match[1] + 'Page ' + page[1] + ' sur ' + page[2] + match[3];
            var range = match[2].match(/^Showing (.+) of (.+)$/);
            if (range) return match[1] + 'Affichage de ' + range[1] + ' sur ' + range[2] + match[3];
            var selected = match[2].match(/^(\d+) selected$/);
            if (selected) return match[1] + selected[1] + ' sélectionné(s)' + match[3];
        }
        return value;
    }

    function translateTree(root) {
        if (!root) return;
        if (root.nodeType === Node.TEXT_NODE) {
            root.nodeValue = translate(root.nodeValue);
            return;
        }
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
        acceptNode: function (node) {
            var parent = node.parentNode;
            return parent && !/^(SCRIPT|STYLE|TEXTAREA)$/i.test(parent.nodeName) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
        }
        });
        var node;
        while ((node = walker.nextNode())) node.nodeValue = translate(node.nodeValue);
        if (root.nodeType === Node.ELEMENT_NODE && root.matches && root.matches('[placeholder], [title], [aria-label]')) translateAttributes(root);
        if (root.querySelectorAll) root.querySelectorAll('[placeholder], [title], [aria-label]').forEach(translateAttributes);
    }

    function translateAttributes(element) {
        ['placeholder', 'title', 'aria-label'].forEach(function (attribute) {
            if (!element.hasAttribute(attribute)) return;
            var original = element.getAttribute(attribute), translated = translate(original);
            if (translated !== original) element.setAttribute(attribute, translated);
        });
    }

    translateTree(document.body);
    new MutationObserver(function (changes) {
        changes.forEach(function (change) {
            if (change.type === 'attributes') translateAttributes(change.target);
            else Array.prototype.forEach.call(change.addedNodes, translateTree);
        });
    }).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['placeholder', 'title', 'aria-label'] });
}());
