{if $tags.fb_app_id}
<meta property="fb:app_id" content="{$tags.fb_app_id}" />
{/if}
<meta property="og:type" content="{$tags.site_type}" />
<meta property="og:site_name" content="{$tags.site_name}" />
<meta property="og:url" content="{$urls.current_url}" />
<meta property="og:title" content="{$tags.title}" />
<meta property="og:description" content="{$tags.description}" />
<meta property="og:image" content="{$urls.shop_domain_url}{$tags.image}" />
{if $tags.site_type=='product'}
<meta property="product:price:amount" content="{$tags.amount}">
<meta property="product:price:currency" content="{$tags.currency}">
{/if}