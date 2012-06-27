{*DM*}
{*include lytebox-gallery
	<script type="text/javascript" language="javascript" src="/interface/themes/ntk/js/Lytebox/lytebox.js"></script>
	<link rel="stylesheet" href="/interface/themes/ntk/js/Lytebox/lytebox.css" type="text/css" media="screen" />
*}
	<br><br>

{*display thumbnails with links to their big-pics using lytebox*}
	{foreach from=$thumbs key=k item=foo}
		<a href={$pics[$k]} class="lytebox" data-lyte-options="group:preview"><img src={$thumbs[$k]}></a>
	{/foreach}
