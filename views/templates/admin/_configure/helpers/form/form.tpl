{extends file="helpers/form/form.tpl"}

{block name="description"}
    {if isset($input.desc) && !empty($input.desc)}
        <p class="help-block">
            {if is_array($input.desc)}
                {foreach $input.desc as $p}
                    {if is_array($p)}
                        <span id="{$p.id}">{$p.text}</span><br />
                    {else}
                        {$p}<br />
                    {/if}
                {/foreach}
            {else}
                {$input.desc}
            {/if}
        </p>
    {/if}
    {if $input.type == 'file' && isset($image)}
            <img src="{$image}" class="img-thumbnail opengraph">
    {/if}
{/block}
