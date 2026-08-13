import {
  hairAnalysisQuestions,
  hairNeedProfiles,
  resultMetricOrder,
} from './hair-analysis-data.js';

const products = Array.isArray(window.marudermHairAnalysisProducts)
  ? window.marudermHairAnalysisProducts
  : [];

const STORAGE_KEY = 'maruderm-hair-analysis-v1';

class HairAnalysisController {
  constructor(root) {
    this.root = root;
    this.answers = {};
    this.currentIndex = 0;
    this.processingTimers = [];
    this.elements = this.getElements();
    this.bindEvents();
    this.restoreSavedState();
  }

  getElements() {
    return {
      start: this.root.querySelector('[data-analysis-start]'),
      startButton: this.root.querySelector('[data-analysis-start-button]'),
      resumeButton: this.root.querySelector('[data-analysis-resume]'),
      quiz: this.root.querySelector('[data-analysis-quiz]'),
      processing: this.root.querySelector('[data-analysis-processing]'),
      result: this.root.querySelector('[data-analysis-result]'),
      question: this.root.querySelector('[data-analysis-question]'),
      context: this.root.querySelector('[data-analysis-context]'),
      section: this.root.querySelector('[data-analysis-section]'),
      current: this.root.querySelector('[data-analysis-current]'),
      total: this.root.querySelector('[data-analysis-total]'),
      progress: this.root.querySelector('[data-analysis-progress]'),
      progressFill: this.root.querySelector('[data-analysis-progress-fill]'),
      back: this.root.querySelector('[data-analysis-back]'),
      next: this.root.querySelector('[data-analysis-next]'),
      hint: this.root.querySelector('[data-analysis-hint]'),
      saveExit: this.root.querySelector('[data-analysis-save-exit]'),
      processingText: this.root.querySelector('[data-analysis-processing-text]'),
    };
  }

  bindEvents() {
    this.elements.startButton.addEventListener('click', () => this.start(false));
    this.elements.resumeButton.addEventListener('click', () => this.start(true));
    this.elements.back.addEventListener('click', () => this.goBack());
    this.elements.next.addEventListener('click', () => this.goNext());
    this.elements.saveExit.addEventListener('click', () => this.saveAndExit());
    this.root
      .querySelector('[data-analysis-restart]')
      .addEventListener('click', () => this.restart());
  }

  restoreSavedState() {
    try {
      const saved = JSON.parse(window.localStorage.getItem(STORAGE_KEY));
      if (!saved?.answers || !Number.isInteger(saved.currentIndex)) return;
      this.answers = saved.answers;
      this.currentIndex = Math.min(saved.currentIndex, hairAnalysisQuestions.length - 1);
      this.elements.resumeButton.hidden = false;
    } catch {
      window.localStorage.removeItem(STORAGE_KEY);
    }
  }

  start(resume) {
    if (!resume) {
      this.answers = {};
      this.currentIndex = 0;
    }
    this.showOnly('quiz');
    this.renderQuestion();
    this.scrollToWorkspace();
  }

  showOnly(name) {
    ['start', 'quiz', 'processing', 'result'].forEach((key) => {
      this.elements[key].hidden = key !== name;
    });
  }

  renderQuestion() {
    const question = hairAnalysisQuestions[this.currentIndex];
    const selected = this.getSelectedValues(question);
    const isMultiple = question.type === 'multiple';
    const options = question.options
      .map((option) => this.optionTemplate(question, option, selected))
      .join('');

    this.elements.question.innerHTML = `
      <div class="hair-question__number">0${this.currentIndex + 1}</div>
      <div class="hair-question__heading">
        <span>${question.section}</span>
        <h2 id="hair-question-title">${question.title}</h2>
        <p>${question.description}</p>
      </div>
      <fieldset class="hair-question__options${isMultiple ? ' hair-question__options--multiple' : ''}" aria-labelledby="hair-question-title">
        <legend class="sr-only">${question.title}</legend>
        ${options}
      </fieldset>`;

    this.elements.context.textContent = question.context;
    this.elements.section.textContent = question.section;
    this.elements.current.textContent = this.currentIndex + 1;
    this.elements.total.textContent = hairAnalysisQuestions.length;
    this.elements.back.disabled = this.currentIndex === 0;
    this.elements.hint.textContent = isMultiple
      ? `Можна обрати до ${question.maxSelections} варіантів`
      : 'Оберіть один варіант';

    const progress = Math.round(((this.currentIndex + 1) / hairAnalysisQuestions.length) * 100);
    this.elements.progress.setAttribute('aria-valuenow', progress);
    this.elements.progressFill.style.width = `${progress}%`;
    this.bindOptionEvents(question);
    this.updateNavigation(question);
  }

  optionTemplate(question, option, selected) {
    const checked = selected.includes(option.value);
    const type = question.type === 'multiple' ? 'checkbox' : 'radio';
    return `
      <label class="hair-option${checked ? ' is-selected' : ''}" data-analysis-option>
        <input type="${type}" name="${question.id}" value="${option.value}" ${checked ? 'checked' : ''}>
        <span class="hair-option__marker">${option.marker}</span>
        <span class="hair-option__copy"><strong>${option.label}</strong><small>${option.detail}</small></span>
        <span class="hair-option__control"><svg><use href="#icon-check"></use></svg></span>
      </label>`;
  }

  bindOptionEvents(question) {
    this.elements.question.querySelectorAll('input').forEach((input) => {
      input.addEventListener('change', () => this.handleSelection(question, input));
    });
  }

  handleSelection(question, input) {
    if (question.type === 'single') {
      this.answers[question.id] = input.value;
    } else {
      const current = this.getSelectedValues(question);
      const option = question.options.find((item) => item.value === input.value);
      let next = input.checked
        ? [...current, input.value]
        : current.filter((value) => value !== input.value);

      if (option?.exclusive && input.checked) next = [input.value];
      if (!option?.exclusive && input.checked) {
        next = next.filter(
          (value) => !question.options.find((item) => item.value === value)?.exclusive,
        );
      }
      if (next.length > question.maxSelections) {
        input.checked = false;
        next = current;
        this.elements.hint.textContent = `Максимум ${question.maxSelections} варіанти`;
      }
      this.answers[question.id] = next;
    }

    this.saveProgress();
    this.renderQuestion();
  }

  getSelectedValues(question) {
    const answer = this.answers[question.id];
    if (question.type === 'multiple') return Array.isArray(answer) ? answer : [];
    return answer ? [answer] : [];
  }

  updateNavigation(question) {
    const hasAnswer = this.getSelectedValues(question).length > 0;
    this.elements.next.disabled = !hasAnswer;
    this.elements.next.innerHTML = `${
      this.currentIndex === hairAnalysisQuestions.length - 1 ? 'Сформувати профіль' : 'Далі'
    } <svg><use href="#icon-arrow"></use></svg>`;
  }

  goBack() {
    if (this.currentIndex === 0) return;
    this.currentIndex -= 1;
    this.renderQuestion();
    this.saveProgress();
  }

  goNext() {
    const question = hairAnalysisQuestions[this.currentIndex];
    if (!this.getSelectedValues(question).length) return;
    if (this.currentIndex < hairAnalysisQuestions.length - 1) {
      this.currentIndex += 1;
      this.renderQuestion();
      this.saveProgress();
      return;
    }
    this.processResults();
  }

  saveProgress() {
    window.localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({ answers: this.answers, currentIndex: this.currentIndex }),
    );
  }

  saveAndExit() {
    this.saveProgress();
    this.elements.resumeButton.hidden = false;
    this.showOnly('start');
    this.scrollToWorkspace();
  }

  processResults() {
    this.showOnly('processing');
    const messages = [
      'Аналізуємо стан шкіри голови…',
      'Зіставляємо структуру та пористість…',
      'Розставляємо пріоритети догляду…',
      'Збираємо персональну рутину…',
    ];
    this.processingTimers.forEach(window.clearTimeout);
    messages.forEach((message, index) => {
      this.processingTimers.push(
        window.setTimeout(() => {
          this.elements.processingText.textContent = message;
        }, index * 430),
      );
    });
    this.processingTimers.push(
      window.setTimeout(() => {
        this.renderResult(this.calculateResult());
        this.showOnly('result');
        window.localStorage.removeItem(STORAGE_KEY);
        this.scrollToWorkspace();
      }, 1900),
    );
  }

  calculateResult() {
    const scores = Object.fromEntries(resultMetricOrder.map((key) => [key, 0]));
    const profile = {};
    let answeredOptions = 0;
    let specialistFlag = false;

    hairAnalysisQuestions.forEach((question) => {
      const values = this.getSelectedValues(question);
      values.forEach((value) => {
        const option = question.options.find((item) => item.value === value);
        if (!option) return;
        answeredOptions += 1;
        specialistFlag ||= option.flag === 'specialist';
        Object.entries(option.scores || {}).forEach(([key, score]) => {
          if (key in scores) scores[key] += score;
        });
        if (question.profileKey) profile[question.profileKey] = option.profileLabel;
      });
    });

    const ranked = resultMetricOrder
      .map((key) => ({ key, score: scores[key] }))
      .sort((a, b) => b.score - a.score);
    const maxScore = Math.max(ranked[0].score, 1);
    const priorities = ranked.slice(0, 3).map((item, index) => ({
      ...item,
      percent: Math.max(38, Math.round((item.score / maxScore) * (92 - index * 5))),
      ...hairNeedProfiles[item.key],
    }));
    const consistency = Math.min(97, 82 + Math.round(answeredOptions / 2));

    return {
      profile,
      priorities,
      primary: hairNeedProfiles[priorities[0].key],
      consistency,
      specialistFlag,
    };
  }

  renderResult(result) {
    const routineValue = this.answers.routine;
    const routineCopy = {
      minimal: 'Залишили тільки базу: очищення, цільова підтримка та захист довжини.',
      balanced: 'База плюс один цільовий крок — достатньо системно, але без складного графіка.',
      advanced: 'Рутина допускає чергування цільових засобів та інтенсивніший догляд.',
    }[routineValue];

    this.root.querySelector('[data-result-title]').textContent = result.primary.title;
    this.root.querySelector('[data-result-summary]').textContent = result.primary.summary;
    this.root.querySelector('[data-result-match]').style.setProperty('--match', result.consistency);
    this.root.querySelector('[data-result-match-value]').textContent = `${result.consistency}%`;
    this.root.querySelector('[data-result-routine-copy]').textContent = routineCopy;
    this.root.querySelector('[data-result-tags]').innerHTML = [
      result.profile.pattern || 'природна текстура',
      result.profile.strand || 'середня товщина',
      result.profile.porosity || 'середня пористість',
      result.profile.routine || 'збалансована рутина',
    ]
      .map((tag) => `<span>${tag}</span>`)
      .join('');

    this.root.querySelector('[data-result-priorities]').innerHTML = result.priorities
      .map(
        (priority, index) => `
          <article class="hair-priority" style="--priority-color: ${priority.color}; --priority-value: ${priority.percent}">
            <div class="hair-priority__top"><span>0${index + 1}</span><strong>${priority.percent}%</strong></div>
            <div class="hair-priority__bar"><i></i></div>
            <h4>${priority.label}</h4>
            <p>${index === 0 ? 'Головний фокус рутини' : index === 1 ? 'Підтримувальна потреба' : 'Додатковий баланс'}</p>
          </article>`,
      )
      .join('');

    const recommendedProducts = result.primary.products
      .map((id) => products.find((product) => product.id === id))
      .filter(Boolean);
    this.root.querySelector('[data-result-products]').innerHTML = recommendedProducts
      .map((product, index) => this.resultProductTemplate(product, index))
      .join('');

    const tips = [...result.primary.tips];
    if (result.specialistFlag) {
      tips[0] = 'Через локальне або помітно посилене випадіння варто звернутися до трихолога.';
    }
    this.root.querySelector('[data-result-tips]').innerHTML = tips
      .map((tip, index) => `<li><span>0${index + 1}</span><p>${tip}</p></li>`)
      .join('');
  }

  resultProductTemplate(product, index) {
    const stepLabels = {
      shampoo: 'Очищення',
      conditioner: 'Догляд за довжиною',
      'scalp-tonic': 'Догляд за шкірою',
      styling: 'Захист і фініш',
    };
    const step = stepLabels[product.subcategory] || 'Цільовий догляд';
    const price = new Intl.NumberFormat('uk-UA').format(product.price);
    return `
      <article class="hair-result-product">
        <div class="hair-result-product__image">
          <span>0${index + 1} · ${step}</span>
          <img src="${product.image}" alt="${product.name}">
        </div>
        <div class="hair-result-product__body">
          <small>${product.categoryLabel}</small>
          <h4><a href="${product.url}">${product.name}</a></h4>
          <div><strong>${price} ₴</strong><a href="${product.url}" aria-label="Переглянути ${product.name}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></a></div>
        </div>
      </article>`;
  }

  restart() {
    this.answers = {};
    this.currentIndex = 0;
    window.localStorage.removeItem(STORAGE_KEY);
    this.elements.resumeButton.hidden = true;
    this.start(false);
  }

  scrollToWorkspace() {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    window.setTimeout(
      () =>
        this.root
          .querySelector('.hair-diagnostic__workspace')
          .scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' }),
      30,
    );
  }
}

document
  .querySelectorAll('[data-hair-analysis-page]')
  .forEach((root) => new HairAnalysisController(root));
