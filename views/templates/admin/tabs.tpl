
<link href="{$backoffice_css|escape:'htmlall':'UTF-8'}" rel="stylesheet" type="text/css">

<div class="row jk-tabs">
    {if $tabs}
        <div class="col-lg-2 col-md-3">
        <nav>
            {foreach $tabs as $tab}
                <a class="tab-title {if isset($active_tab) && $tab.id==$active_tab}active{/if}" href="#" id="{$tab.id}" data-target="#jk-tabs-{$tab.id|escape:'htmlall':'UTF-8'}">{$tab.title|escape:'htmlall':'UTF-8'}</a>
            {/foreach}
            </nav>
        </div>
        <div class="col-lg-10 col-md-9">
            <div class="content">
            {foreach $tabs as $tab}
                <div class="tab-content" id="jk-tabs-{$tab.id}" style="display:{if isset($active_tab) && $tab.id==$active_tab}block{else}none{/if}">
                    {html_entity_decode($tab.content)}
                </div>
            {/foreach}
            </div>
        </div>
    {/if}
</div>

{literal}
    <script>

            $('.jk-tabs nav .tab-title').click(function () {
                var elem = $(this);
                var target = $(elem.data('target'));
                elem.addClass('active').siblings().removeClass('active');
                target.show().siblings().hide();
            });

    </script>
{/literal}