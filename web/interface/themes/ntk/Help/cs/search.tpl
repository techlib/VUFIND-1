<h1>Užitečné tipy pro vyhledávání</h1>

<ul class="HelpMenu">
  <li><a href="#Wildcard Searches">Vyhledávání se zástupnými znaky</a></li>
  <li><a href="#Fuzzy Searches">Fuzzy vyhledávání</a></li>
  <li><a href="#Proximity Searches">Proximitní vyhledávání</a></li>
  <li><a href="#Range Searches">Vyhledávání v rozmezí</a></li>
  <li><a href="#Boosting a Term">Zdůrazňování hledaného termínu</a></li>
  <li><a href="#Boolean operators">Booleovské operátory</a>
    <ul>
      <li><a href="#OR">OR</a></li>
      <li><a href="#AND">AND</a></li>
      <li><a href="#+">+</a></li>
      <li><a href="#NOT">NOT</a></li>
      <li><a href="#-">-</a></li>
    </ul>
  </li>
</ul>

<dl class="Content">
  <dt><a name="Wildcard Searches"></a>Vyhledávání se zástupnými znaky</dt>
  <dd>
    <p>Jako jediný zástupný znak slouží symbol otazníku: <strong>?</strong></p>
    <p>Například, k vyhledání výrazů <q>text</q> nebo <q>test</q> můžete použít vyhledávací dotaz:</p>
    <pre class="code">te?t</pre>
    <p>Jako zástupný znak pro 0 a více znaků slouží symbol hvězdičky: <strong>*</strong></p>
    <p>Například, pro vyhledávání výrazů <q>test</q>, <q>testy</q> nebo <q>testování</q> můžete použít vyhledávací dotaz:</p>
    <pre class="code">test*</pre>
    <p>Zástupné znaky můžete také používat uprostřed výrazů. Nelze je však použít namísto prvního znaku výrazu.</p>
    <pre class="code">te*t</pre>
  </dd>
  
  <dt><a name="Fuzzy Searches"></a>Fuzzy vyhledávání</dt>
  <dd>
    <p>Použijte symbol vlnovky (<strong>~</strong>) na konci <strong>jednoslovného</strong> výrazu. Například, pro vyhledání výrazů podobných slovu <q>test</q>, zadejte vyhledávací dotaz:</p>
    <pre class="code">test~</pre>
    <p>Tímto způsobem získáte výsledky obsahující výrazy jako <q>lest</q> nebo <q>testy</q>.</p>
    <p>Jako nepovinný parametr můžete určit požadovanou podobnost. Jde o hodnotu mezi 0 a 1, přičemž hodnota bližší 1 bude hledat pouze více podobné výrazy. Například:</p>
    <pre class="code">test~0.8</pre>
    <p>Pokud není míra podobnosti určena, použije se výchozí hodnota 0.5.</p>
  </dd>
  
  <dt><a name="Proximity Searches"></a>Proximitní vyhledávání</dt>
  <dd>
    <p>
      Použijte symbol vlnovky (<strong>~</strong>) na konci <strong>víceslovného</strong> výrazu.
      Například, pro vyhledání výrazů <q>ekonomika</q> a <q>Keynes</q>, které se nacházejí nejvýše 10 slov od sebe, použijte:
    </p>
    <pre class="code">"ekonomika Keynes"~10</pre>
  </dd>
  
  {literal}
  <dt><a name="Range Searches"></a>Vyhledávání v rozmezí</dt>
  <dd>
    <p>
      K provedení vyhledávání omezeno rozmezím, můžete použít symbol složených závorek (<strong>{ }</strong>).
      Například, pro vyhledávání výrazů, které začínají buď s A, B nebo C:
    </p>
    <pre class="code">{A TO C}</pre>
    <p>
      Stejná věc může být provedena s číselnými poli jako je třeba rok:
    </p>
    <pre class="code">[2002 TO 2003]</pre>
  </dd>
  {/literal}
  
  <dt><a name="Boosting a Term"></a>Zdůrazňování hledaného termínu</dt>
  <dd>
    <p>
      Ke zdůraznění významu výrazu můžete použít symbol <strong>^</strong>.
      Například, můžete zkusit následující vyhledávací dotaz:
    </p>
    <pre class="code">ekonomika Keynes^5</pre>
    <p>V tomto případě je větší váha přiřazena slovu "Keynes".</p>
  </dd>
  
  <dt><a name="Boolean operators"></a>Booleovské operátory</dt>
  <dd>
    <p>
      Booleovské operátory umožňují kombinaci výrazů pomocí logických operátorů.
      Fungují následující operátory: <strong>AND</strong>, <strong>+</strong>, <strong>OR</strong>, <strong>NOT</strong> a <strong>-</strong>.
    </p>
    <p>Poznámka: Booleovské operátory musí být zapisovány VELKÝMI PÍSMENY.</p>
    <dl>
      <dt><a name="OR"></a>OR</dt>
      <dd>
        <p>Operátor <strong>OR</strong> je výchozím způsobem spojení výrazů. V případě, že mezi vyhledávanými výrazy není žádný Booleovský operátor, je pro jejich spojení použito OR. Operátor OR spojuje dva výrazy a způsobuje, že jsou nalezeny ty záznamy, které obsahují alespoň jeden z hledaných výrazů.</p>
        <p>Pro vyhledání záznamů, které obsahují buď <q>ekonomika Keynes</q> nebo pouze <q>Keynes</q>, použijte vyhledávací dotaz:</p>
        <pre class="code">"ekonomika Keynes" Keynes</pre>
        <p>nebo</p>
        <pre class="code">"ekonomika Keynes" OR Keynes</pre>
      </dd>
      
      <dt><a name="AND"></a>AND</dt>
      <dd>
        <p>Operátor AND vyhledá záznamy, které obsahují na jakémkoli místě oba výrazy, kterou jsou jím spojeny.</p>
        <p>Pro vyhledání záznamů, které obsahují výrazy <q>ekonomika</q> a zároveň <q>Keynes</q>, použijte dotaz: </p>
        <pre class="code">"ekonomika" AND "Keynes"</pre>
      </dd>
      <dt><a name="+"></a>+</dt>
      <dd>
        <p>Operátor <q>+</q> způsobuje, že jsou nalezeny pouze záznamy, které obsahují výraz následující tento operátor.</p>
        <p>Pro vyhledání záznamů, které musí obsahovat <q>ekonomika</q> a mohou obsahovat <q>Keynes</q>, použijte vyhledávací dotaz: </p>
        <pre class="code">+ekonomika Keynes</pre>
      </dd>
      <dt><a name="NOT"></a>NOT</dt>
      <dd>
        <p>Operátor NOT vyřazuje z množiny vyhledaných záznamů ty, které obsahují výraz následující po tomto operátoru.</p>
        <p>Pro vyhledání záznamů, které obsahují <q>ekonomika</q>, ale neobsahují <q>Keynes</q>, použijte vyhledávací dotaz: </p>
        <pre class="code">"ekonomika" NOT "Keynes"</pre>
        <p>Poznámka: Operátor NOT nemůže být použit pouze s jedním výrazem. Pro ilustraci, následující vyhledávací dotaz nebude mít žádné výsledky:</p>
        <pre class="code">NOT "ekonomika"</pre>
      </dd>
      <dt><a name="-"></a>-</dt>
      <dd>
        <p>Operátor <strong>-</strong> neboli prohibiční operátor vypustí z množiny výsledků ty záznamy, které obsahují výraz, který po tomto operátoru následuje.</p>
        <p>Pro vyhledání dokumentů, které obsahují <q>ekonomika</q>, ale ne <q>Keynes</q>, použijte vyhledávací dotaz: </p>
        <pre class="code">"ekonomika" -"Keynes"</pre>
      </dd>
    </dl>
  </dd>
</dl>
