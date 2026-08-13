<?php
/** @var string[] $heroImages */
?>
<svg class="svg-symbols" aria-hidden="true">
  <symbol id="icon-arrow" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"></path></symbol>
  <symbol id="icon-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"></path></symbol>
</svg>
<section class="hair-diagnostic" data-hair-analysis-page>
  <div class="hair-diagnostic__hero">
    <div class="shell hair-diagnostic__hero-grid">
      <div class="hair-diagnostic__hero-copy">
        <span class="kicker">Smart hair diagnostic</span>
        <h1>Догляд починається з розуміння <em>твого волосся.</em></h1>
        <p>
          Відповідай на кілька точних запитань — ми складемо профіль волосся, визначимо пріоритети
          та покажемо логіку персональної рутини.
        </p>
        <div class="hair-diagnostic__hero-meta" aria-label="Інформація про діагностику">
          <span><strong>10</strong> запитань</span><span><strong>≈ 3</strong> хвилини</span
          ><span><strong>1</strong> персональний профіль</span>
        </div>
      </div>
      <div class="hair-diagnostic__visual" aria-hidden="true">
        <span class="hair-diagnostic__halo"></span>
        <span class="hair-diagnostic__strand hair-diagnostic__strand--one"></span>
        <span class="hair-diagnostic__strand hair-diagnostic__strand--two"></span>
        <span class="hair-diagnostic__visual-note hair-diagnostic__visual-note--scalp"
          >Шкіра голови</span
        >
        <span class="hair-diagnostic__visual-note hair-diagnostic__visual-note--structure"
          >Структура</span
        >
        <span class="hair-diagnostic__visual-note hair-diagnostic__visual-note--routine"
          >Ритм догляду</span
        >
        <div class="hair-diagnostic__product-stage">
          <img src="<?= esc_url($heroImages[0]); ?>" alt="" />
          <img src="<?= esc_url($heroImages[1]); ?>" alt="" />
          <span>Точніше, ніж просто «сухе» чи «жирне»</span>
        </div>
      </div>
    </div>
  </div>

  <div class="shell hair-diagnostic__workspace">
    <section class="hair-diagnostic__start" data-analysis-start>
      <div class="hair-diagnostic__start-copy">
        <span class="hair-diagnostic__eyebrow">Перед стартом</span>
        <h2>Оцінюй волосся таким, яким воно є більшість днів.</h2>
        <p>
          Тут немає правильних відповідей. Обирай найближчий варіант, а не винятковий день після
          салону чи укладки.
        </p>
        <button class="button button--dark" type="button" data-analysis-start-button>
          Почати діагностику <svg><use href="#icon-arrow"></use></svg>
        </button>
        <button class="hair-diagnostic__resume" type="button" data-analysis-resume hidden>
          Продовжити збережену діагностику →
        </button>
      </div>
      <div class="hair-diagnostic__start-points">
        <article>
          <span>01</span><strong>Поведінка шкіри голови</strong>
          <p>Жирність, сухість, чутливість і частота миття.</p>
        </article>
        <article>
          <span>02</span><strong>Структура полотна</strong>
          <p>Форма, товщина, пористість і схильність до ламкості.</p>
        </article>
        <article>
          <span>03</span><strong>Реальний спосіб життя</strong>
          <p>Фарбування, тепло, цілі та час, який зручно вкладати.</p>
        </article>
      </div>
    </section>

    <section class="hair-diagnostic__quiz" data-analysis-quiz hidden>
      <div class="hair-diagnostic__progress-head">
        <div>
          <span data-analysis-section>Базовий профіль</span>
          <strong
            ><span data-analysis-current>1</span> / <span data-analysis-total>10</span></strong
          >
        </div>
        <button type="button" data-analysis-save-exit>Зберегти й вийти</button>
      </div>
      <div
        class="hair-diagnostic__progress"
        role="progressbar"
        aria-label="Прогрес діагностики"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-valuenow="10"
        data-analysis-progress
      >
        <span data-analysis-progress-fill></span>
      </div>

      <div class="hair-diagnostic__question-layout">
        <div class="hair-diagnostic__question" data-analysis-question aria-live="polite"></div>
        <aside class="hair-diagnostic__context">
          <span>Чому це важливо</span>
          <p data-analysis-context></p>
          <div class="hair-diagnostic__context-signal">
            <i></i><i></i><i></i><i></i><i></i>
            <small>Аналізуємо взаємозв’язки, а не одну відповідь</small>
          </div>
        </aside>
      </div>

      <div class="hair-diagnostic__navigation">
        <button type="button" class="hair-diagnostic__back" data-analysis-back>← Назад</button>
        <span data-analysis-hint>Оберіть один варіант</span>
        <button type="button" class="button button--dark" data-analysis-next disabled>
          Далі <svg><use href="#icon-arrow"></use></svg>
        </button>
      </div>
    </section>

    <section class="hair-diagnostic__processing" data-analysis-processing hidden aria-live="polite">
      <div class="hair-diagnostic__processing-orbit" aria-hidden="true"><i></i><i></i><i></i></div>
      <span>Формуємо профіль</span>
      <h2>Зіставляємо твої відповіді</h2>
      <p data-analysis-processing-text>Аналізуємо стан шкіри голови…</p>
    </section>

    <section class="hair-diagnostic__result" data-analysis-result hidden aria-live="polite">
      <header class="hair-result__header">
        <div class="hair-result__intro">
          <span class="hair-diagnostic__eyebrow">Твій hair profile</span>
          <h2 data-result-title></h2>
          <p data-result-summary></p>
          <div class="hair-result__tags" data-result-tags></div>
        </div>
        <div class="hair-result__match" data-result-match style="--match: 92">
          <div><strong data-result-match-value>92%</strong><span>точність профілю</span></div>
          <small>на основі узгодженості відповідей</small>
        </div>
      </header>

      <div class="hair-result__section-head">
        <div>
          <span class="hair-diagnostic__eyebrow">Карта потреб</span>
          <h3>На чому зосередити догляд</h3>
        </div>
        <p>Відсотки показують відносний пріоритет, а не медичний показник.</p>
      </div>
      <div class="hair-result__priorities" data-result-priorities></div>

      <div class="hair-result__routine">
        <div class="hair-result__section-head">
          <div>
            <span class="hair-diagnostic__eyebrow">Персональна рутина</span>
            <h3 data-result-routine-title>Три кроки без перевантаження</h3>
          </div>
          <p data-result-routine-copy></p>
        </div>
        <div class="hair-result__products" data-result-products></div>
      </div>

      <div class="hair-result__guidance">
        <div>
          <span class="hair-diagnostic__eyebrow">Твої правила догляду</span>
          <h3>Маленькі зміни, які підтримають результат.</h3>
        </div>
        <ol data-result-tips></ol>
      </div>

      <footer class="hair-result__footer">
        <p>
          Діагностика має інформаційний характер і не замінює консультацію дерматолога або
          трихолога. За раптового чи інтенсивного випадіння зверніться до фахівця.
        </p>
        <button type="button" class="hair-diagnostic__restart" data-analysis-restart>
          ↻ Пройти ще раз
        </button>
      </footer>
    </section>
  </div>
</section>
