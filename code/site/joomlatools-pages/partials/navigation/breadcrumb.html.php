
<? //Generate html
$route = explode('/', page()->path)
?>

<ul class="breadcrumb-list list-none">
    <? foreach($route as $key => $segment):  ?>
        <? $segments[] = $segment; ?>
        <? if ($key != count($route) - 1) : ?>
            <li class="inline">
                <a href="<?= route(implode('/', $segments)) ?>">
                    <span><?= ucfirst($segment); ?></span></a>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M12.95 10.707l.707-.707L8 4.343 6.586 5.757 10.828 10l-4.242 4.243L8 15.657l4.95-4.95z"/></svg>
            </li>
        <? else : ?>
            <li class="inline is-active">
                <span itemprop="name"><?= page()->isCollection() ? ucfirst($segment) : page()->title ?></span>
            </li>
        <? endif; ?>
    <? endforeach; ?>
</ul>

<? //Generate microdata
$segments  = [];
$microdata = data([
    "@context" => "https://schema.org",
    "@type"    => "BreadcrumbList",
    'itemListElement' => []
]);

foreach ($route as $key => $segment)
{
    $segments[] = $segment;
    $microdata->itemListElement = [
        "@type"    => "ListItem",
        "position" => $key + 1,
        "name"     => rtrim(page()->isCollection() ? ucfirst($segment) : page()->title, '.'),
        "item"     =>(string) url(route(implode('/', $segments)))
    ];
}
?>

<script data-inline type="application/ld+json">
<?= $microdata ?>
</script>