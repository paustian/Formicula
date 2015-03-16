<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title>{gt text='Make a reservation'}</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <base href="{$baseurl}" />
    </head>
    <body>
        <p>
            {gt text='Hello %s,' tag1=$userdata.uname}<br /><br />
            
            {gt text='Thank you for your reservation.'}<br /><br />
            
            {gt text='Kind regards'}<br />
            {$sitename}<br /><br />
            
            {gt text='This data was sent to us:'}<br /><br />

            {gt text='Your Name'} : {$userdata.uname}<br />
            {gt text='Email'} : {$userdata.uemail}<br />

            {foreach item=field from=$custom}
            {$field.name} : {$field.data}<br />
            {/foreach}
            <br />
            {gt text='Comment'} : {$userdata.comment|safehtml|nl2br}<br />
            <br />

        </p>
    </body>
</html>
