{include file='forms/1_userheader.tpl'}

<div style="text-align: center;">
    {gt text='This data was sent to us:'}
    <br /><br />
    {gt text='Thanks for applying, we will keep your data strictly confidential'}
    <br /><br />
</div>

<table width="80%">

    <tr>
        <td style="width: 50%; font-weight:bold; text-align:right; padding-right: 50px;">
            {gt text='Your Name'}&nbsp;:
        </td>
        <td style="width: 50%; text-align:left; padding-left: 50px;">
            {$userdata.uname|safehtml}
        </td>
    </tr>

    {foreach item=field from=$custom}
    <tr>
        <td style="width: 50%; font-weight:bold; text-align:right; padding-right: 50px;">
            {$field.name|safetext}&nbsp;:
        </td>
        <td style="width: 50%; text-align:left; padding-left: 50px;">
            {$field.data|safehtml}
        </td>
    </tr>
    {/foreach}

    <tr>
        <td style="width: 50%; font-weight:bold; text-align:right; padding-right: 50px;">
            {gt text='Email'}&nbsp;:
        </td>
        <td style="width: 50%; text-align:left; padding-left: 50px;">
            {$userdata.uemail|safehtml}
        </td>
    </tr>

    <tr>
        <td style="width: 50%; font-weight:bold; text-align:right; padding-right: 50px;">
            {gt text='Homepage'}&nbsp;:
        </td>
        <td style="width: 50%; text-align:left; padding-left: 50px;">
            {$userdata.url|safehtml}
        </td>
    </tr>

    <tr>
        <td style="width: 50%; font-weight:bold; text-align:right; padding-right: 50px;">
            {gt text='Comment'}&nbsp;:
        </td>
        <td style="width: 50%; text-align:left; padding-left: 50px;">
            {$userdata.comment|safehtml|nl2br}
        </td>
    </tr>

</table>

<br />
{if $sendtouser==true}
{gt text='Confirmation of your submission will be emailed to you in a few minutes.'}
{else}
{gt text='There was an internal error when sending confirmation mail'}
{/if}

{include file='forms/1_userfooter.tpl'}
