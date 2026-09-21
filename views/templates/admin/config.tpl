<style>
/* ── reset scope ── */
.pnp { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
.pnp *, .pnp *::before, .pnp *::after { box-sizing: border-box; }

/* ── top bar ── */
.pnp-topbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 4px 0 22px; flex-wrap: wrap; gap: 12px;
}
.pnp-brand { display: flex; align-items: center; gap: 12px; }
.pnp-brand img { height: 26px; }
.pnp-brand-name { font-size: .95rem; font-weight: 700; color: #111827; line-height: 1.2; }
.pnp-brand-sub  { font-size: .72rem; color: #9ca3af; }
.pnp-topbar-actions { display: flex; align-items: center; gap: 10px; }

/* ── environment pill ── */
.pnp-env-pill {
  display: inline-flex; background: #f3f4f6; border-radius: 8px; padding: 3px; gap: 2px;
}
.pnp-env-pill-btn {
  padding: 5px 14px; border-radius: 6px; font-size: .75rem; font-weight: 600;
  cursor: pointer; border: none; background: transparent; color: #6b7280;
  transition: all .15s; white-space: nowrap;
}
.pnp-env-pill-btn.active        { background: #fff; color: #111827; box-shadow: 0 1px 3px rgba(0,0,0,.12); }
.pnp-env-pill-btn.active::before { content: '● '; }
.pnp-env-pill-btn[data-env="0"].active { color: #166534; }
.pnp-env-pill-btn[data-env="1"].active { color: #92400e; }

/* ── save button ── */
.pnp-btn-save {
  background: #ef0643; border: none; color: #fff; padding: 8px 22px;
  border-radius: 8px; font-weight: 600; font-size: .83rem;
  cursor: pointer; transition: background .15s; white-space: nowrap;
}
.pnp-btn-save:hover { background: #c9032f; }

/* ── alerts ── */
.pnp-alert {
  display: flex; align-items: center; gap: 8px; padding: 12px 16px;
  border-radius: 8px; font-size: .83rem; margin-bottom: 14px;
}
.pnp-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.pnp-alert-danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.pnp-alert i { font-size: 1rem; flex-shrink: 0; }

/* ── card ── */
.pnp-card {
  background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
  margin-bottom: 14px; overflow: hidden;
  box-shadow: 0 1px 2px rgba(0,0,0,.04);
}
.pnp-card-head {
  padding: 14px 20px; display: flex; align-items: center; gap: 10px;
  border-bottom: 1px solid #f3f4f6; background: #fafafa;
}
.pnp-card-head-between { justify-content: space-between; }
.pnp-card-head-left  { display: flex; align-items: center; gap: 10px; }
.pnp-card-ico {
  width: 30px; height: 30px; border-radius: 7px; background: #f3f4f6;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.pnp-card-ico i { font-size: .95rem; color: #6b7280; }
.pnp-card-title { font-size: .88rem; font-weight: 600; color: #111827; }
.pnp-card-subtitle { font-size: .75rem; color: #9ca3af; margin-top: 1px; }
.pnp-card-body { padding: 20px; }

/* ── field ── */
.pnp-f { margin-bottom: 16px; }
.pnp-f:last-child { margin-bottom: 0; }
.pnp-f > label {
  display: block; font-size: .72rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .05em; color: #6b7280; margin-bottom: 5px;
}
.pnp-fc {
  display: block; width: 100%; height: 38px;
  border: 1.5px solid #d1d5db; border-radius: 8px;
  padding: 0 12px; font-size: .875rem; color: #111827;
  background: #fff; outline: none; transition: border-color .15s, box-shadow .15s;
  -webkit-appearance: none;
}
.pnp-fc:focus { border-color: #ef0643; box-shadow: 0 0 0 3px rgba(239,6,67,.1); }
.pnp-fc::placeholder { color: #c4c8d0; }
.pnp-hint { font-size: .73rem; color: #9ca3af; margin-top: 4px; }

/* ── row ── */
.pnp-row { display: flex; gap: 14px; }
.pnp-row .pnp-f { flex: 1; min-width: 0; }
.pnp-row .pnp-f.w2 { flex: 2; }
.pnp-row .pnp-f.w3 { flex: 3; }

/* ── inline toggle ── */
.pnp-itoggle { position: relative; width: 36px; height: 20px; flex-shrink: 0; display: inline-block; cursor: pointer; margin:10px;}
.pnp-itoggle input { position: absolute; opacity: 0; width: 0; height: 0; }
.pnp-itoggle-track {
  position: absolute; inset: 0; background: #d1d5db; border-radius: 10px; transition: background .18s;
}
.pnp-itoggle input:checked + .pnp-itoggle-track { background: #ef0643; }
.pnp-itoggle-thumb {
  position: absolute; top: 2px; left: 2px; width: 16px; height: 16px;
  background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.25);
  transition: transform .18s; pointer-events: none;
}
.pnp-itoggle input:checked ~ .pnp-itoggle-thumb { transform: translateX(16px); }

/* ── toggle rows (3DS / save cards) ── */
.pnp-toggle-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 16px; border: 1.5px solid #e5e7eb; border-radius: 10px;
  cursor: pointer; transition: all .15s; margin-bottom: 10px; gap: 12px;
}
.pnp-toggle-row:last-child { margin-bottom: 0; }
.pnp-toggle-row:hover { border-color: #d1d5db; background: #fafafa; }
.pnp-toggle-row.on { border-color: #ef0643; background: #fff9fa; }
.pnp-toggle-row-info { display: flex; align-items: center; gap: 10px; }
.pnp-toggle-row-ico {
  width: 32px; height: 32px; border-radius: 8px; background: #f3f4f6;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.pnp-toggle-row.on .pnp-toggle-row-ico { background: #fff0f3; }
.pnp-toggle-row-ico i { font-size: .95rem; color: #9ca3af; }
.pnp-toggle-row.on .pnp-toggle-row-ico i { color: #ef0643; }
.pnp-toggle-row-text strong { display: block; font-size: .83rem; font-weight: 600; color: #111827; }
.pnp-toggle-row-text span  { font-size: .73rem; color: #9ca3af; }

/* ── color row ── */
.pnp-color-wrap { display: flex; align-items: center; gap: 8px; }
.pnp-color-swatch {
  width: 38px; height: 38px; border-radius: 8px; border: 1.5px solid #d1d5db;
  overflow: hidden; flex-shrink: 0; cursor: pointer;
}
.pnp-color-swatch input[type="color"] {
  width: 160%; height: 160%; margin: -30%; border: none; padding: 0; cursor: pointer;
}
.pnp-color-wrap .pnp-fc { flex: 1; }

/* ── env blocks ── */
.pnp-cred-sandbox { display: none; }
.pnp-env-sandbox .pnp-cred-prod    { display: none; }
.pnp-env-sandbox .pnp-cred-sandbox { display: block; }
.pnp-env-block { border-radius: 8px; padding: 14px 16px 2px; margin-bottom: 0; }
.pnp-env-block + .pnp-env-block { margin-top: 8px; }
.pnp-env-block-prod    { background: #f0fdf4; border: 1px solid #bbf7d0; }
.pnp-env-block-sandbox { background: #fffbeb; border: 1px solid #fde68a; }
.pnp-env-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: .67rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .06em; padding: 2px 8px; border-radius: 20px; margin-bottom: 10px;
}
.pnp-env-badge-prod    { background: #dcfce7; color: #166534; }
.pnp-env-badge-sandbox { background: #fef3c7; color: #92400e; }
.pnp-env-dot { width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0; }
.pnp-env-badge-prod    .pnp-env-dot { background: #16a34a; }
.pnp-env-badge-sandbox .pnp-env-dot { background: #d97706; }

/* ── mode options ── */
.pnp-modes { display: flex; gap: 10px; }
.pnp-mode {
  flex: 1; border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 14px 15px;
  cursor: pointer; display: flex; align-items: flex-start; gap: 10px; transition: all .15s;
}
.pnp-mode:hover { border-color: #ef0643; }
.pnp-mode.active { border-color: #ef0643; background: #fff9fa; }
.pnp-mode input[type="radio"] { display: none; }
.pnp-mode-radio {
  width: 16px; height: 16px; border: 2px solid #d1d5db; border-radius: 50%;
  flex-shrink: 0; margin-top: 2px; display: flex; align-items: center; justify-content: center;
  transition: border-color .15s;
}
.pnp-mode.active .pnp-mode-radio { border-color: #ef0643; }
.pnp-mode-dot { width: 7px; height: 7px; border-radius: 50%; background: #ef0643; transform: scale(0); transition: transform .15s; }
.pnp-mode.active .pnp-mode-dot { transform: scale(1); }
.pnp-mode-text strong { display: block; font-size: .83rem; font-weight: 600; color: #111827; margin-bottom: 2px; }
.pnp-mode-text small  { font-size: .73rem; color: #9ca3af; line-height: 1.4; }

/* ── checkout sub-panels ── */
#pnp-checkout-fields { padding-top: 16px; }
.pnp-sub {
  background: #f9fafb; border: 1px solid #f0f0f0; border-radius: 8px;
  padding: 14px 16px; margin-bottom: 10px;
}
.pnp-sub:last-child { margin-bottom: 0; }
.pnp-sub-title {
  font-size: .67rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
  color: #9ca3af; margin: 0 0 12px; display: flex; align-items: center; gap: 5px;
}
.pnp-sub-title i { font-size: .85rem; vertical-align: -1px; }

/* ── method chips ── */
.pnp-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
.pnp-chip {
  position: relative; display: inline-flex; align-items: center; gap: 5px;
  border: 1.5px solid #e5e7eb; border-radius: 20px; padding: 5px 12px;
  cursor: pointer; font-size: .78rem; font-weight: 500; color: #6b7280;
  background: #fff; user-select: none; line-height: 1; transition: all .13s;
}
.pnp-chip:hover { border-color: #ef0643; color: #ef0643; }
.pnp-chip.active { border-color: #ef0643; background: #fff5f7; color: #ef0643; font-weight: 600; }
.pnp-chip input { position: absolute; opacity: 0; width: 0; height: 0; }
.pnp-chip-chk {
  width: 13px; height: 13px; border-radius: 50%; background: #ef0643;
  display: none; align-items: center; justify-content: center; flex-shrink: 0;
}
.pnp-chip.active .pnp-chip-chk { display: inline-flex; }
.pnp-chip-chk::after {
  content: ''; width: 3px; height: 5px; border: 1.5px solid #fff;
  border-top: none; border-left: none; transform: rotate(45deg) translate(-1px,-1px);
}

/* ── theme picker ── */
.pnp-themes { display: flex; gap: 10px; margin-bottom: 14px; }
.pnp-theme {
  width: 84px; border: 2px solid #e5e7eb; border-radius: 8px;
  overflow: hidden; cursor: pointer; flex-shrink: 0; transition: border-color .15s;
}
.pnp-theme input { display: none; }
.pnp-theme.active { border-color: #ef0643; }
.pnp-theme-mock { height: 46px; padding: 8px; display: flex; flex-direction: column; gap: 4px; }
.pnp-theme-ln { border-radius: 3px; }
.pnp-theme-label {
  padding: 4px 8px; font-size: .67rem; font-weight: 700; text-align: center;
  text-transform: uppercase; letter-spacing: .04em;
}
.pnp-theme[data-theme="LIGHT"] .pnp-theme-mock { background: #eef0f3; }
.pnp-theme[data-theme="LIGHT"] .pnp-theme-ln:nth-child(1) { background: #c2c8d0; height: 4px; width: 60%; }
.pnp-theme[data-theme="LIGHT"] .pnp-theme-ln:nth-child(2) { background: #dde0e6; height: 3px; width: 42%; }
.pnp-theme[data-theme="LIGHT"] .pnp-theme-ln:nth-child(3) { background: #ef0643; height: 8px; width: 46%; border-radius: 3px; margin-top: auto; }
.pnp-theme[data-theme="LIGHT"] .pnp-theme-label { background: #fff; color: #4b5563; border-top: 1px solid #e5e7eb; }
.pnp-theme[data-theme="DARK"] .pnp-theme-mock { background: #12112b; }
.pnp-theme[data-theme="DARK"] .pnp-theme-ln:nth-child(1) { background: #3a3870; height: 4px; width: 60%; }
.pnp-theme[data-theme="DARK"] .pnp-theme-ln:nth-child(2) { background: #2c2a54; height: 3px; width: 42%; }
.pnp-theme[data-theme="DARK"] .pnp-theme-ln:nth-child(3) { background: #ef0643; height: 8px; width: 46%; border-radius: 3px; margin-top: auto; }
.pnp-theme[data-theme="DARK"] .pnp-theme-label { background: #0a0a1a; color: #7b7aad; border-top: 1px solid #1e1d3e; }

/* ── bottom save bar ── */
.pnp-footer {
  display: flex; align-items: center; justify-content: flex-end; gap: 12px;
  padding: 16px 0 4px;
}
.pnp-saved-msg { display: none; align-items: center; gap: 5px; font-size: .8rem; font-weight: 600; color: #166534; }
.pnp-saved-msg.visible { display: flex; }
.pnp-saved-msg i { font-size: .9rem; }

/* ── divider ── */
.pnp-sep { border: none; border-top: 1px solid #f3f4f6; margin: 16px 0; }

/* ── card grid ── */
.pnp-cards-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  align-items: start;
}
.pnp-cards-grid .pnp-card { margin-bottom: 0; }
.pnp-span2 { grid-column: span 2; }
@media (max-width: 900px) {
  .pnp-cards-grid { grid-template-columns: 1fr; }
  .pnp-span2 { grid-column: span 1; }
}
</style>

<div class="pnp">

{* Alerts *}
{if $success}
<div class="pnp-alert pnp-alert-success">
  <i class="material-icons">check_circle</i>
  {l s='Settings saved successfully.' mod='paylands'}
</div>
{/if}
{foreach from=$errors item=err}
<div class="pnp-alert pnp-alert-danger">
  <i class="material-icons">error_outline</i>
  {$err|escape:'html':'UTF-8'}
</div>
{/foreach}

<form action="{$form_action}&token={$token}" method="post" id="pnp-form"
  class="{if $config.PNP_ENVIRONMENT == 1}pnp-env-sandbox{/if}">
  <input type="hidden" name="btnSubmit" value="1">
  <input type="hidden" name="PNP_ENVIRONMENT" value="0" id="pnp-env-val">

  {* ── Top bar ── *}
  <div class="pnp-topbar">
    <div class="pnp-brand">
      <img src="https://paynopain.com/wp-content/uploads/2025/08/paynopain.svg" alt="Paynopain">
      <div>
        <div class="pnp-brand-name">Paylands Gateway</div>
        <div class="pnp-brand-sub">paynopain.com</div>
      </div>
    </div>
    <div class="pnp-topbar-actions">
      <div class="pnp-env-pill" id="pnp-env-pill">
        <button type="button" class="pnp-env-pill-btn {if $config.PNP_ENVIRONMENT == 0}active{/if}" data-env="0">
          {l s='Production' mod='paylands'}
        </button>
        <button type="button" class="pnp-env-pill-btn {if $config.PNP_ENVIRONMENT == 1}active{/if}" data-env="1">
          {l s='Sandbox' mod='paylands'}
        </button>
      </div>
      <button type="submit" class="pnp-btn-save">{l s='Save Settings' mod='paylands'}</button>
    </div>
  </div>

  <div class="pnp-cards-grid">

  {* ════════════════════════════════════════
     CARD 1 — API CREDENTIALS
     ════════════════════════════════════════ *}
  <div class="pnp-card">
    <div class="pnp-card-head">
      <div class="pnp-card-ico"><i class="material-icons">vpn_key</i></div>
      <div>
        <div class="pnp-card-title">{l s='API Credentials' mod='paylands'}</div>
        <div class="pnp-card-subtitle">{l s='API key and signature from your Paylands dashboard.' mod='paylands'}</div>
      </div>
    </div>
    <div class="pnp-card-body">

      <div class="pnp-cred-prod pnp-env-block pnp-env-block-prod">
        <span class="pnp-env-badge pnp-env-badge-prod"><span class="pnp-env-dot"></span>{l s='Production' mod='paylands'}</span>
        <div class="pnp-row">
          <div class="pnp-f">
            <label>{l s='API Key' mod='paylands'}</label>
            <input class="pnp-fc" type="text" name="PNP_KEY_PROD"
              value="{$config.PNP_KEY_PROD|escape:'html':'UTF-8'}" placeholder="API_KEY...">
          </div>
          <div class="pnp-f">
            <label>{l s='Signature' mod='paylands'}</label>
            <input class="pnp-fc" type="text" name="PNP_SIGNATURE_PROD"
              value="{$config.PNP_SIGNATURE_PROD|escape:'html':'UTF-8'}">
          </div>
        </div>
      </div>

      <div class="pnp-cred-sandbox pnp-env-block pnp-env-block-sandbox">
        <span class="pnp-env-badge pnp-env-badge-sandbox"><span class="pnp-env-dot"></span>{l s='Sandbox' mod='paylands'}</span>
        <div class="pnp-row">
          <div class="pnp-f">
            <label>{l s='API Key' mod='paylands'}</label>
            <input class="pnp-fc" type="text" name="PNP_KEY_SANDBOX"
              value="{$config.PNP_KEY_SANDBOX|escape:'html':'UTF-8'}" placeholder="API_KEY...">
          </div>
          <div class="pnp-f">
            <label>{l s='Signature' mod='paylands'}</label>
            <input class="pnp-fc" type="text" name="PNP_SIGNATURE_SANDBOX"
              value="{$config.PNP_SIGNATURE_SANDBOX|escape:'html':'UTF-8'}">
          </div>
        </div>
      </div>

    </div>
  </div>

  {* ════════════════════════════════════════
     CARD 2 — CARD PAYMENT
     ════════════════════════════════════════ *}
  <div class="pnp-card">
    <div class="pnp-card-head">
      <div class="pnp-card-ico"><i class="material-icons">credit_card</i></div>
      <div>
        <div class="pnp-card-title">{l s='Card Payment' mod='paylands'}</div>
        <div class="pnp-card-subtitle">{l s='Service UUID and checkout button label.' mod='paylands'}</div>
      </div>
    </div>
    <div class="pnp-card-body">

      <div class="pnp-cred-prod pnp-env-block pnp-env-block-prod">
        <span class="pnp-env-badge pnp-env-badge-prod"><span class="pnp-env-dot"></span>{l s='Production' mod='paylands'}</span>
        <div class="pnp-row">
          <div class="pnp-f w3">
            <label>{l s='Service UUID' mod='paylands'}</label>
            <input class="pnp-fc" type="text" name="PNP_SERVICE_PROD"
              value="{$config.PNP_SERVICE_PROD|escape:'html':'UTF-8'}" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
          </div>
          <div class="pnp-f w2">
            <label>{l s='Button Label' mod='paylands'}</label>
            <input class="pnp-fc" type="text" name="PNP_SERVICE_NAME_PROD"
              value="{$config.PNP_SERVICE_NAME_PROD|escape:'html':'UTF-8'}" placeholder="{l s='Pay with Card Safely' mod='paylands'}">
          </div>
        </div>
      </div>

      <div class="pnp-cred-sandbox pnp-env-block pnp-env-block-sandbox">
        <span class="pnp-env-badge pnp-env-badge-sandbox"><span class="pnp-env-dot"></span>{l s='Sandbox' mod='paylands'}</span>
        <div class="pnp-row">
          <div class="pnp-f w3">
            <label>{l s='Service UUID' mod='paylands'}</label>
            <input class="pnp-fc" type="text" name="PNP_SERVICE_SANDBOX"
              value="{$config.PNP_SERVICE_SANDBOX|escape:'html':'UTF-8'}" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
          </div>
          <div class="pnp-f w2">
            <label>{l s='Button Label' mod='paylands'}</label>
            <input class="pnp-fc" type="text" name="PNP_SERVICE_NAME_SANDBOX"
              value="{$config.PNP_SERVICE_NAME_SANDBOX|escape:'html':'UTF-8'}" placeholder="{l s='Pay with Card Safely' mod='paylands'}">
          </div>
        </div>
      </div>

    </div>
  </div>

  {* ════════════════════════════════════════
     CARD 3 — BIZUM
     ════════════════════════════════════════ *}
  <div class="pnp-card">
    <div class="pnp-card-head pnp-card-head-between">
      <div class="pnp-card-head-left">
        <div class="pnp-card-ico"><i class="material-icons">smartphone</i></div>
        <div>
          <div class="pnp-card-title">{l s='Bizum' mod='paylands'}</div>
          <div class="pnp-card-subtitle">{l s='Standalone Bizum payment option with separate service UUID.' mod='paylands'}</div>
        </div>
      </div>
      <input type="hidden" name="PNP_BIZUM_ENABLED" value="0">
      <label class="pnp-itoggle" id="pnp-bizum-toggle" title="{l s='Enable Bizum' mod='paylands'}">
        <input type="checkbox" id="pnp-bizum-cb" name="PNP_BIZUM_ENABLED" value="1"
          {if $config.PNP_BIZUM_ENABLED == 1}checked{/if}>
        <div class="pnp-itoggle-track"></div>
        <div class="pnp-itoggle-thumb"></div>
      </label>
    </div>
    <div class="pnp-card-body" id="pnp-bizum-body" {if $config.PNP_BIZUM_ENABLED != 1}style="display:none"{/if}>

      <div class="pnp-cred-prod pnp-env-block pnp-env-block-prod">
        <span class="pnp-env-badge pnp-env-badge-prod"><span class="pnp-env-dot"></span>{l s='Production' mod='paylands'}</span>
        <div class="pnp-row">
          <div class="pnp-f w3">
            <label>{l s='Bizum Service UUID' mod='paylands'}</label>
            <input class="pnp-fc" type="text" name="PNP_BIZUM_SERVICE_PROD"
              value="{$config.PNP_BIZUM_SERVICE_PROD|escape:'html':'UTF-8'}" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
          </div>
          <div class="pnp-f w2">
            <label>{l s='Button Label' mod='paylands'}</label>
            <input class="pnp-fc" type="text" name="PNP_BIZUM_NAME_PROD"
              value="{$config.PNP_BIZUM_NAME_PROD|escape:'html':'UTF-8'}" placeholder="{l s='Pay with Bizum' mod='paylands'}">
          </div>
        </div>
      </div>

      <div class="pnp-cred-sandbox pnp-env-block pnp-env-block-sandbox">
        <span class="pnp-env-badge pnp-env-badge-sandbox"><span class="pnp-env-dot"></span>{l s='Sandbox' mod='paylands'}</span>
        <div class="pnp-row">
          <div class="pnp-f w3">
            <label>{l s='Bizum Service UUID' mod='paylands'}</label>
            <input class="pnp-fc" type="text" name="PNP_BIZUM_SERVICE_SANDBOX"
              value="{$config.PNP_BIZUM_SERVICE_SANDBOX|escape:'html':'UTF-8'}" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
          </div>
          <div class="pnp-f w2">
            <label>{l s='Button Label' mod='paylands'}</label>
            <input class="pnp-fc" type="text" name="PNP_BIZUM_NAME_SANDBOX"
              value="{$config.PNP_BIZUM_NAME_SANDBOX|escape:'html':'UTF-8'}" placeholder="{l s='Pay with Bizum' mod='paylands'}">
          </div>
        </div>
      </div>

    </div>
  </div>

  {* ════════════════════════════════════════
     CARD 4 — ORDER FLOW
     ════════════════════════════════════════ *}
  <div class="pnp-card">
    <div class="pnp-card-head">
      <div class="pnp-card-ico"><i class="material-icons">account_tree</i></div>
      <div>
        <div class="pnp-card-title">{l s='Order Flow' mod='paylands'}</div>
        <div class="pnp-card-subtitle">{l s='Security settings and order state mapping.' mod='paylands'}</div>
      </div>
    </div>
    <div class="pnp-card-body">

      <input type="hidden" name="PNP_SECURE" value="0">
      <label class="pnp-toggle-row {if $config.PNP_SECURE == 1}on{/if}" id="pnp-secure-row" for="pnp-secure">
        <div class="pnp-toggle-row-info">
          <div class="pnp-toggle-row-text">
            <strong>{l s='3-D Secure' mod='paylands'}</strong>
            <span>{l s='Require 3DS authentication on every payment.' mod='paylands'}</span>
          </div>
        </div>
        <div class="pnp-itoggle">
          <input type="checkbox" id="pnp-secure" name="PNP_SECURE" value="1"
            {if $config.PNP_SECURE == 1}checked{/if}>
          <div class="pnp-itoggle-track"></div>
          <div class="pnp-itoggle-thumb"></div>
        </div>
      </label>

      <input type="hidden" name="PNP_SAVE_CARD" value="0">
      <label class="pnp-toggle-row {if $config.PNP_SAVE_CARD == 1}on{/if}" id="pnp-savecard-row" for="pnp-savecard">
        <div class="pnp-toggle-row-info">
          
          <div class="pnp-toggle-row-text">
            <strong>{l s='Save Cards' mod='paylands'}</strong>
            <span>{l s='Let customers save cards for faster future checkouts.' mod='paylands'}</span>
          </div>
        </div>
        <div class="pnp-itoggle">
          <input type="checkbox" id="pnp-savecard" name="PNP_SAVE_CARD" value="1"
            {if $config.PNP_SAVE_CARD == 1}checked{/if}>
          <div class="pnp-itoggle-track"></div>
          <div class="pnp-itoggle-thumb"></div>
        </div>
      </label>

      <hr class="pnp-sep">

      <div class="pnp-row">
        <div class="pnp-f">
          <label>{l s='Awaiting Payment State' mod='paylands'}</label>
          <select class="pnp-fc" name="PNP_ORDER_WAITING_STATE">
            {foreach from=$order_statuses item=status}
            <option value="{$status.id_option}" {if $config.PNP_ORDER_WAITING_STATE == $status.id_option}selected{/if}>
              {$status.name|escape:'html':'UTF-8'}
            </option>
            {/foreach}
          </select>
        </div>
        <div class="pnp-f">
          <label>{l s='Payment Accepted State' mod='paylands'}</label>
          <select class="pnp-fc" name="PNP_ORDER_SUCCESS_STATE">
            {foreach from=$order_statuses item=status}
            <option value="{$status.id_option}" {if $config.PNP_ORDER_SUCCESS_STATE == $status.id_option}selected{/if}>
              {$status.name|escape:'html':'UTF-8'}
            </option>
            {/foreach}
          </select>
        </div>
      </div>

    </div>
  </div>

  {* ════════════════════════════════════════
     CARD 5 — APPEARANCE
     ════════════════════════════════════════ *}
  <div class="pnp-card pnp-span2">
    <div class="pnp-card-head">
      <div class="pnp-card-ico"><i class="material-icons">palette</i></div>
      <div>
        <div class="pnp-card-title">{l s='Appearance' mod='paylands'}</div>
        <div class="pnp-card-subtitle">{l s='Logo, accent color and language for the payment form.' mod='paylands'}</div>
      </div>
    </div>
    <div class="pnp-card-body">

      <div class="pnp-row">
        <div class="pnp-f w3">
          <label>{l s='Payment Form Logo URL' mod='paylands'}</label>
          <input class="pnp-fc" type="text" name="PNP_LOGO"
            value="{$config.PNP_LOGO|escape:'html':'UTF-8'}">
        </div>
        <div class="pnp-f w2">
          <label>{l s='Accent Color' mod='paylands'}</label>
          <div class="pnp-color-wrap">
            <div class="pnp-color-swatch">
              <input type="color" id="pnp-color-picker" value="{$config.PNP_FACE_COLOR|escape:'html':'UTF-8'}">
            </div>
            <input class="pnp-fc" type="text" id="pnp-color" name="PNP_FACE_COLOR"
              value="{$config.PNP_FACE_COLOR|escape:'html':'UTF-8'}" placeholder="#ef0643" maxlength="7">
          </div>
        </div>
      </div>

      <div class="pnp-f" style="max-width:200px;">
        <label>{l s='Form Language' mod='paylands'}</label>
        <select class="pnp-fc" name="PNP_FORM_LANG">
          {foreach from=$form_languages item=lang}
          <option value="{$lang.value}" {if $config.PNP_FORM_LANG == $lang.value}selected{/if}>
            {$lang.label|escape:'html':'UTF-8'}
          </option>
          {/foreach}
        </select>
      </div>

    </div>
  </div>

  {* ════════════════════════════════════════
     CARD 6 — CHECKOUT MODE
     ════════════════════════════════════════ *}
  <div class="pnp-card pnp-span2">
    <div class="pnp-card-head">
      <div class="pnp-card-ico"><i class="material-icons">tune</i></div>
      <div>
        <div class="pnp-card-title">{l s='Checkout Mode' mod='paylands'}</div>
        <div class="pnp-card-subtitle">{l s='Choose how the payment form is configured.' mod='paylands'}</div>
      </div>
    </div>
    <div class="pnp-card-body">

      <div class="pnp-modes">
        <label class="pnp-mode {if $config.PNP_MODE == 0}active{/if}" id="pnp-mode-0">
          <input type="radio" name="PNP_MODE" value="0" {if $config.PNP_MODE == 0}checked{/if}>
          <div class="pnp-mode-radio"><div class="pnp-mode-dot"></div></div>
          <div class="pnp-mode-text">
            <strong>{l s='Service UUID' mod='paylands'}</strong>
            <small>{l s='Classic hosted payment form. Uses the Service UUID set in the Card Payment section.' mod='paylands'}</small>
          </div>
        </label>
        <label class="pnp-mode {if $config.PNP_MODE == 1}active{/if}" id="pnp-mode-1">
          <input type="radio" name="PNP_MODE" value="1" {if $config.PNP_MODE == 1}checked{/if}>
          <div class="pnp-mode-radio"><div class="pnp-mode-dot"></div></div>
          <div class="pnp-mode-text">
            <strong>{l s='Checkout' mod='paylands'}</strong>
            <small>{l s='Configurable checkout with selectable methods, theme and a dedicated UUID.' mod='paylands'}</small>
          </div>
        </label>
      </div>

      <div id="pnp-checkout-fields" {if $config.PNP_MODE == 0}style="display:none;"{/if}>

        {* UUID & Button Label *}
        <div class="pnp-sub">
          <p class="pnp-sub-title"><i class="material-icons">fingerprint</i>{l s='Checkout UUID & Button Label' mod='paylands'}</p>
          <div class="pnp-cred-prod pnp-env-block pnp-env-block-prod">
            <span class="pnp-env-badge pnp-env-badge-prod"><span class="pnp-env-dot"></span>{l s='Production' mod='paylands'}</span>
            <div class="pnp-row">
              <div class="pnp-f w3">
                <label>{l s='Checkout UUID' mod='paylands'}</label>
                <input class="pnp-fc" type="text" name="PNP_CHECKOUT_UUID_PROD"
                  value="{$config.PNP_CHECKOUT_UUID_PROD|escape:'html':'UTF-8'}"
                  placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
              </div>
              <div class="pnp-f w2">
                <label>{l s='Button Label' mod='paylands'}</label>
                <input class="pnp-fc" type="text" name="PNP_CHECKOUT_NAME_PROD"
                  value="{$config.PNP_CHECKOUT_NAME_PROD|escape:'html':'UTF-8'}"
                  placeholder="{l s='Pay Securely' mod='paylands'}">
              </div>
            </div>
          </div>
          <div class="pnp-cred-sandbox pnp-env-block pnp-env-block-sandbox">
            <span class="pnp-env-badge pnp-env-badge-sandbox"><span class="pnp-env-dot"></span>{l s='Sandbox' mod='paylands'}</span>
            <div class="pnp-row">
              <div class="pnp-f w3">
                <label>{l s='Checkout UUID' mod='paylands'}</label>
                <input class="pnp-fc" type="text" name="PNP_CHECKOUT_UUID_SANDBOX"
                  value="{$config.PNP_CHECKOUT_UUID_SANDBOX|escape:'html':'UTF-8'}"
                  placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
              </div>
              <div class="pnp-f w2">
                <label>{l s='Button Label' mod='paylands'}</label>
                <input class="pnp-fc" type="text" name="PNP_CHECKOUT_NAME_SANDBOX"
                  value="{$config.PNP_CHECKOUT_NAME_SANDBOX|escape:'html':'UTF-8'}"
                  placeholder="{l s='Pay Securely' mod='paylands'}">
              </div>
            </div>
          </div>
        </div>

        {* Payment methods *}
        <div class="pnp-sub">
          <p class="pnp-sub-title"><i class="material-icons">payments</i>{l s='Payment Methods' mod='paylands'}</p>
          <div class="pnp-chips">
            {foreach from=$payment_methods item=method}
            <label class="pnp-chip {if $config[$method.config_key]}active{/if}">
              <input type="checkbox" name="{$method.config_key}" value="{$method.api_value}"
                {if $config[$method.config_key]}checked{/if}>
              <span class="pnp-chip-chk"></span>
              {$method.label|escape:'html':'UTF-8'}
            </label>
            {/foreach}
          </div>
          <div class="pnp-f" style="max-width:200px; margin-bottom:0;">
            <label>{l s='Default Method' mod='paylands'}</label>
            <select class="pnp-fc" name="PNP_CHECKOUT_DEFAULT_METHOD">
              {foreach from=$payment_methods item=method}
              <option value="{$method.api_value}" {if $config.PNP_CHECKOUT_DEFAULT_METHOD == $method.api_value}selected{/if}>
                {$method.label|escape:'html':'UTF-8'}
              </option>
              {/foreach}
            </select>
          </div>
        </div>

        {* Appearance *}
        <div class="pnp-sub">
          <p class="pnp-sub-title"><i class="material-icons">palette</i>{l s='Appearance' mod='paylands'}</p>
          <div class="pnp-f">
            <label>{l s='Theme' mod='paylands'}</label>
            <div class="pnp-themes">
              <label class="pnp-theme {if $config.PNP_CHECKOUT_THEME == 'LIGHT'}active{/if}" data-theme="LIGHT">
                <input type="radio" name="PNP_CHECKOUT_THEME" value="LIGHT" {if $config.PNP_CHECKOUT_THEME == 'LIGHT'}checked{/if}>
                <div class="pnp-theme-mock">
                  <div class="pnp-theme-ln"></div><div class="pnp-theme-ln"></div><div class="pnp-theme-ln"></div>
                </div>
                <div class="pnp-theme-label">{l s='Light' mod='paylands'}</div>
              </label>
              <label class="pnp-theme {if $config.PNP_CHECKOUT_THEME == 'DARK'}active{/if}" data-theme="DARK">
                <input type="radio" name="PNP_CHECKOUT_THEME" value="DARK" {if $config.PNP_CHECKOUT_THEME == 'DARK'}checked{/if}>
                <div class="pnp-theme-mock">
                  <div class="pnp-theme-ln"></div><div class="pnp-theme-ln"></div><div class="pnp-theme-ln"></div>
                </div>
                <div class="pnp-theme-label">{l s='Dark' mod='paylands'}</div>
              </label>
            </div>
          </div>
          <div class="pnp-row">
            <div class="pnp-f">
              <label>{l s='Font' mod='paylands'}</label>
              <input class="pnp-fc" type="text" name="PNP_CHECKOUT_FONT"
                value="{$config.PNP_CHECKOUT_FONT|escape:'html':'UTF-8'}" placeholder="Sans Serif">
            </div>
            <div class="pnp-f w2">
              <label>{l s='Description' mod='paylands'}</label>
              <input class="pnp-fc" type="text" name="PNP_CHECKOUT_DESCRIPTION"
                value="{$config.PNP_CHECKOUT_DESCRIPTION|escape:'html':'UTF-8'}"
                placeholder="{l s='Shown at the top of the checkout.' mod='paylands'}">
            </div>
          </div>
        </div>

      </div>{* /checkout-fields *}
    </div>
  </div>

  </div>{* /pnp-cards-grid *}

  {* ── Footer save bar ── *}
  <div class="pnp-footer">
    <span class="pnp-saved-msg {if $success}visible{/if}" id="pnp-saved-msg">
      <i class="material-icons">check_circle</i>{l s='Saved' mod='paylands'}
    </span>
    <button type="submit" class="pnp-btn-save">{l s='Save Settings' mod='paylands'}</button>
  </div>

</form>
</div>

<script>
(function($) {

  var $form = $('#pnp-form');

  /* ── Environment pill ── */
  $('#pnp-env-pill .pnp-env-pill-btn').on('click', function() {
    var env = parseInt($(this).data('env'));
    $('#pnp-env-val').val(env);
    $('#pnp-env-pill .pnp-env-pill-btn').removeClass('active');
    $(this).addClass('active');
    $form.toggleClass('pnp-env-sandbox', env === 1);
  });

  /* ── Bizum toggle ── */
  $('#pnp-bizum-cb').on('change', function() {
    $('#pnp-bizum-body').stop(true, true)[this.checked ? 'slideDown' : 'slideUp'](180);
  });

  /* ── Toggle rows (3DS / save cards) ── */
  $('#pnp-secure').on('change', function() {
    $('#pnp-secure-row').toggleClass('on', this.checked);
  });
  $('#pnp-savecard').on('change', function() {
    $('#pnp-savecard-row').toggleClass('on', this.checked);
  });

  /* ── Color picker ── */
  $('#pnp-color-picker').on('input', function() { $('#pnp-color').val(this.value); });
  $('#pnp-color').on('input', function() {
    if (/^#[0-9a-f]{6}$/i.test(this.value)) $('#pnp-color-picker').val(this.value);
  });

  /* ── Checkout mode ── */
  function syncMode() {
    var val = $('input[name="PNP_MODE"]:checked').val();
    $('.pnp-mode').each(function() {
      $(this).toggleClass('active', $(this).find('input').val() === val);
    });
    $('#pnp-checkout-fields').stop(true, true)[val === '1' ? 'slideDown' : 'slideUp'](200);
  }
  $('input[name="PNP_MODE"]').on('change', syncMode);
  $('.pnp-mode').on('click', function() {
    $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
  });

  /* ── Method chips ── */
  $('.pnp-chip input').on('change', function() {
    $(this).closest('.pnp-chip').toggleClass('active', this.checked);
  });

  /* ── Theme picker ── */
  $('input[name="PNP_CHECKOUT_THEME"]').on('change', function() {
    $('.pnp-theme').removeClass('active');
    $(this).closest('.pnp-theme').addClass('active');
  });
  $('.pnp-theme').on('click', function() {
    $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
  });

})(jQuery);
</script>
