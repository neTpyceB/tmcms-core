function rewem2nortex(style, local_part, domain_name, tld) {
    var address = local_part +'@'+ domain_name +'.'+ tld;

	if (arguments.length === 4) {
		document.write('<a href="mailto:'+ address +'" class="'+ style +'">'+ address +'</a>');
	} else {
		var s = ['<a href="mailto:'+ address +'" class="' + style + '">'],
            so = arguments.length;

		for (var n = 4; n < so; n++)
            s[s.length] = arguments[n];

		s[s.length] = '</a>';
		document.write(s.join(''));
	}
}