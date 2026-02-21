<template>
  <div class="ac-page">

    <!-- Toast -->
    <transition name="toast">
      <div v-if="toast.show" class="toast" :class="toast.type === 'success' ? 'toast--ok' : 'toast--err'">
        {{ toast.type === 'success' ? '✓' : '✗' }} {{ toast.msg }}
      </div>
    </transition>

    <!-- Page header -->
    <div class="ac-topbar">
      <div>
        <h1 class="ac-title">Account Settings</h1>
        <p class="ac-sub">Manage your profile and security</p>
      </div>
    </div>

    <div class="ac-grid">

      <!-- ── LEFT: Profile card ──────────────────────────────────── -->
      <div class="profile-card">
        <div class="profile-banner"></div>
        <div class="profile-body">
          <div class="profile-avatar-wrap">
            <div class="profile-avatar">{{ userInitials }}</div>
            <span class="online-dot"></span>
          </div>
          <h2 class="profile-name">{{ userName }}</h2>
          <p class="profile-role">{{ userRole }}</p>

          <div class="profile-info-rows">
            <div class="info-row">
              <span class="info-icon">✉️</span>
              <span class="info-val">{{ userEmail || '—' }}</span>
            </div>
            <div class="info-row">
              <span class="info-icon">📱</span>
              <span class="info-val">{{ profileForm.contact_number || 'No phone added' }}</span>
            </div>
            <div class="info-row">
              <span class="info-icon">📍</span>
              <span class="info-val">{{ profileForm.address || 'No address added' }}</span>
            </div>
            <div class="info-row">
              <span class="info-icon">🕐</span>
              <span class="info-val">Joined {{ joinedDate }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── RIGHT: Forms ───────────────────────────────────────── -->
      <div class="forms-col">

        <!-- Profile form -->
        <div class="form-card">
          <div class="form-card-head">
            <div class="fch-icon">👤</div>
            <div>
              <h3 class="fch-title">Profile Information</h3>
              <p class="fch-sub">Update your personal details</p>
            </div>
          </div>

          <div class="form-body" v-if="!loadingProfile">
            <!-- Name row -->
            <div class="field-row">
              <div class="field">
                <label class="field-label">First Name <span class="req">*</span></label>
                <input v-model="profileForm.first_name" type="text" placeholder="First name"
                  class="field-input" :class="{'field-input--err': pErr.first_name}" />
                <p v-if="pErr.first_name" class="field-err">{{ pErr.first_name }}</p>
              </div>
              <div class="field">
                <label class="field-label">Middle Name <span class="opt">(optional)</span></label>
                <input v-model="profileForm.middle_name" type="text" placeholder="Middle name" class="field-input" />
              </div>
              <div class="field">
                <label class="field-label">Last Name <span class="req">*</span></label>
                <input v-model="profileForm.last_name" type="text" placeholder="Last name"
                  class="field-input" :class="{'field-input--err': pErr.last_name}" />
                <p v-if="pErr.last_name" class="field-err">{{ pErr.last_name }}</p>
              </div>
            </div>

            <!-- Email -->
            <div class="field">
              <label class="field-label">Email Address <span class="req">*</span></label>
              <input v-model="profileForm.email" type="email" placeholder="your@email.com"
                class="field-input" :class="{'field-input--err': pErr.email}" />
              <p v-if="pErr.email" class="field-err">{{ pErr.email }}</p>
            </div>

            <!-- Contact + Address -->
            <div class="field-row">
              <div class="field">
                <label class="field-label">Contact Number <span class="opt">(optional)</span></label>
                <input v-model="profileForm.contact_number" type="tel" placeholder="0000 000 0000" class="field-input" />
              </div>
              <div class="field">
                <label class="field-label">Address <span class="opt">(optional)</span></label>
                <input v-model="profileForm.address" type="text" placeholder="Your address" class="field-input" />
              </div>
            </div>

            <div class="form-footer">
              <button class="btn btn--primary" :disabled="savingProfile" @click="saveProfile">
                <svg v-if="savingProfile" class="spin-ico" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                {{ savingProfile ? 'Saving…' : 'Save Changes' }}
              </button>
            </div>
          </div>

          <div v-else class="form-loading">
            <div class="spin-lg"></div>
            Loading profile…
          </div>
        </div>

        <!-- Password form -->
        <div class="form-card">
          <div class="form-card-head">
            <div class="fch-icon">🔑</div>
            <div>
              <h3 class="fch-title">Change Password</h3>
              <p class="fch-sub">Keep your account secure</p>
            </div>
          </div>

          <div class="form-body">
            <!-- Strength bar -->
            <div v-if="pwForm.new_password" class="pw-strength">
              <div class="pw-bars">
                <div v-for="n in 4" :key="n" class="pw-bar"
                  :style="{ background: n <= pwScore.score ? pwScore.color : '#e2e8f0' }"></div>
              </div>
              <span class="pw-label" :style="{ color: pwScore.color }">{{ pwScore.label }}</span>
            </div>

            <!-- Current pw -->
            <div class="field">
              <label class="field-label">Current Password <span class="req">*</span></label>
              <div class="pw-field">
                <input v-model="pwForm.current_password" :type="show.current ? 'text':'password'"
                  placeholder="Enter current password"
                  class="field-input" :class="{'field-input--err': pwErr.current_password}" />
                <button type="button" class="eye-btn" @click="show.current=!show.current">
                  {{ show.current ? '🙈' : '👁' }}
                </button>
              </div>
              <p v-if="pwErr.current_password" class="field-err">{{ pwErr.current_password }}</p>
            </div>

            <!-- New + Confirm -->
            <div class="field-row">
              <div class="field">
                <label class="field-label">New Password <span class="req">*</span></label>
                <div class="pw-field">
                  <input v-model="pwForm.new_password" :type="show.new ? 'text':'password'"
                    placeholder="Min 6 characters"
                    class="field-input" :class="{'field-input--err': pwErr.new_password}" />
                  <button type="button" class="eye-btn" @click="show.new=!show.new">{{ show.new?'🙈':'👁' }}</button>
                </div>
                <p v-if="pwErr.new_password" class="field-err">{{ pwErr.new_password }}</p>
              </div>
              <div class="field">
                <label class="field-label">Confirm Password <span class="req">*</span></label>
                <div class="pw-field">
                  <input v-model="pwForm.new_password_confirmation" :type="show.confirm ? 'text':'password'"
                    placeholder="Repeat new password"
                    class="field-input" :class="{'field-input--err': pwErr.new_password_confirmation}" />
                  <button type="button" class="eye-btn" @click="show.confirm=!show.confirm">{{ show.confirm?'🙈':'👁' }}</button>
                </div>
                <p v-if="pwErr.new_password_confirmation" class="field-err">{{ pwErr.new_password_confirmation }}</p>
              </div>
            </div>

            <!-- Tips -->
            <div class="pw-tips">
              <div v-for="t in pwTips" :key="t.label" class="pw-tip" :class="{ 'pw-tip--met': t.met }">
                <span class="pw-tip-dot">{{ t.met ? '✓' : '○' }}</span>
                {{ t.label }}
              </div>
            </div>

            <div class="form-footer">
              <button class="btn btn--primary" :disabled="savingPw" @click="changePassword">
                <svg v-if="savingPw" class="spin-ico" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                {{ savingPw ? 'Updating…' : 'Update Password' }}
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'

const { userObj, userName, userRole, userEmail, userInitials, refreshUser } = useAuth()

// ─── Derived display data ─────────────────────────────────────────
const joinedDate = computed(() => {
  const u = userObj.value
  if (!u?.created_at) return '—'
  return new Date(u.created_at).toLocaleDateString('en-US', { month:'long', year:'numeric' })
})

// ─── Profile form ─────────────────────────────────────────────────
const loadingProfile = ref(false)
const savingProfile  = ref(false)

const profileForm = reactive({
  first_name:    '',
  middle_name:   '',
  last_name:     '',
  email:         '',
  contact_number:'',
  address:       '',
})
const pErr = reactive({ first_name:'', last_name:'', email:'' })

// Populate form from stored user object
// Backend returns full_name (single string), so we split it.
// If your backend ever returns first_name/last_name separately, adjust here.
const populateForm = () => {
  const u = userObj.value
  if (!u) return

  // Split full_name into first / last  (handles "Juan dela Cruz" → first="Juan", last="dela Cruz")
  const full = u.full_name || u.name || ''
  const parts = full.trim().split(/\s+/)
  profileForm.first_name  = parts[0]  || ''
  profileForm.last_name   = parts.length > 1 ? parts.slice(1).join(' ') : ''
  profileForm.middle_name = ''                      // not stored separately in your current schema
  profileForm.email          = u.email           || ''
  profileForm.contact_number = u.contact_number  || u.contact || ''
  profileForm.address        = u.address         || ''
}

const validateProfile = () => {
  pErr.first_name = profileForm.first_name.trim() ? '' : 'First name is required'
  pErr.last_name  = profileForm.last_name.trim()  ? '' : 'Last name is required'
  pErr.email      = !profileForm.email.trim() ? 'Email is required'
                  : !/\S+@\S+\.\S+/.test(profileForm.email) ? 'Invalid email format' : ''
  return !pErr.first_name && !pErr.last_name && !pErr.email
}

const saveProfile = async () => {
  if (!validateProfile()) return
  savingProfile.value = true
  try {
    const token = localStorage.getItem('token')
    const full_name = [profileForm.first_name, profileForm.middle_name, profileForm.last_name]
      .filter(Boolean).join(' ').trim()

    const res = await fetch('/api/account/profile', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        full_name,
        email:          profileForm.email,
        contact_number: profileForm.contact_number,
        address:        profileForm.address,
      })
    })

    const data = await res.json()
    if (!res.ok) throw new Error(data.message || 'Failed to save')

    // Update localStorage so Header + Sidebar refresh
    const stored = JSON.parse(localStorage.getItem('user') || '{}')
    stored.full_name       = full_name
    stored.email           = profileForm.email
    stored.contact_number  = profileForm.contact_number
    stored.address         = profileForm.address
    localStorage.setItem('user', JSON.stringify(stored))
    refreshUser()

    showToast('Profile updated successfully!', 'success')
  } catch (e) {
    showToast(e.message || 'Failed to update profile', 'error')
  } finally { savingProfile.value = false }
}

// ─── Password form ────────────────────────────────────────────────
const savingPw = ref(false)
const pwForm = reactive({ current_password:'', new_password:'', new_password_confirmation:'' })
const pwErr  = reactive({ current_password:'', new_password:'', new_password_confirmation:'' })
const show   = reactive({ current:false, new:false, confirm:false })

const pwTips = computed(() => [
  { label:'At least 6 characters', met: pwForm.new_password.length >= 6 },
  { label:'Uppercase letter',       met: /[A-Z]/.test(pwForm.new_password) },
  { label:'Number included',        met: /\d/.test(pwForm.new_password) },
  { label:'Special character',      met: /[^A-Za-z0-9]/.test(pwForm.new_password) },
])
const pwScore = computed(() => {
  const s = pwTips.value.filter(t=>t.met).length
  return [
    { score:0, color:'#e2e8f0', label:'' },
    { score:1, color:'#ef4444', label:'Weak' },
    { score:2, color:'#f97316', label:'Fair' },
    { score:3, color:'#eab308', label:'Good' },
    { score:4, color:'#10b981', label:'Strong' },
  ][s]
})

const validatePw = () => {
  pwErr.current_password         = pwForm.current_password.trim() ? '' : 'Current password is required'
  pwErr.new_password             = !pwForm.new_password.trim() ? 'New password is required'
                                 : pwForm.new_password.length < 6 ? 'Minimum 6 characters' : ''
  pwErr.new_password_confirmation = !pwForm.new_password_confirmation.trim() ? 'Please confirm password'
                                 : pwForm.new_password_confirmation !== pwForm.new_password ? 'Passwords do not match' : ''
  return !pwErr.current_password && !pwErr.new_password && !pwErr.new_password_confirmation
}

const changePassword = async () => {
  if (!validatePw()) return
  savingPw.value = true
  try {
    const token = localStorage.getItem('token')
    const email = userObj.value?.email || profileForm.email

    // Matches your AuthenticatedSessionController@change endpoint exactly:
    // POST /api/auth/change-password
    // body: { email, current_password, new_password, new_password_confirmation }
    const res = await fetch('/api/auth/change-password', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        email,
        current_password:          pwForm.current_password,
        new_password:               pwForm.new_password,
        new_password_confirmation:  pwForm.new_password_confirmation,
      })
    })

    const data = await res.json()
    if (!res.ok) {
      // Handle 422 "Current password is incorrect"
      if (res.status === 422) pwErr.current_password = data.message
      else throw new Error(data.message || 'Failed to change password')
      return
    }

    pwForm.current_password = ''
    pwForm.new_password = ''
    pwForm.new_password_confirmation = ''
    showToast('Password changed successfully!', 'success')
  } catch (e) {
    showToast(e.message || 'Failed to change password', 'error')
  } finally { savingPw.value = false }
}

// ─── Toast ────────────────────────────────────────────────────────
const toast = reactive({ show:false, msg:'', type:'success' })
let toastTimer = null
const showToast = (msg, type='success') => {
  clearTimeout(toastTimer)
  toast.msg=msg; toast.type=type; toast.show=true
  toastTimer = setTimeout(()=>{ toast.show=false }, 3500)
}

onMounted(() => populateForm())
</script>

<style scoped>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

.ac-page {
  min-height:100vh; padding:24px;
  background:#f0f4f8; font-family:'Segoe UI',sans-serif;
}

/* Toast */
.toast {
  position:fixed; top:24px; right:24px; z-index:9999;
  padding:12px 20px; border-radius:14px;
  font-size:13px; font-weight:600;
  box-shadow:0 12px 30px rgba(0,0,0,.2);
}
.toast--ok  { background:linear-gradient(135deg,#1a4972,#0f2f4a); color:white; }
.toast--err { background:linear-gradient(135deg,#dc2626,#991b1b); color:white; }
.toast-enter-active,.toast-leave-active { transition:all .3s ease; }
.toast-enter-from,.toast-leave-to { opacity:0; transform:translateX(20px) scale(.97); }

/* Topbar */
.ac-topbar { margin-bottom:24px; }
.ac-title  { font-size:24px; font-weight:800; color:#1a4972; letter-spacing:-.02em; }
.ac-sub    { font-size:12px; color:#64748b; margin-top:4px; }

/* Grid */
.ac-grid {
  display:grid; grid-template-columns:260px 1fr; gap:20px; max-width:1000px;
}
@media(max-width:768px){ .ac-grid{ grid-template-columns:1fr; } }

/* Profile card */
.profile-card { background:white; border-radius:20px; border:1px solid #e8edf2; overflow:hidden; height:fit-content; }
.profile-banner { height:80px; background:linear-gradient(135deg,#1a4972,#2d6db5); }
.profile-body { padding:0 20px 24px; }
.profile-avatar-wrap { position:relative; width:fit-content; margin:-28px auto 12px; }
.profile-avatar {
  width:56px; height:56px; border-radius:50%;
  background:linear-gradient(135deg,#1a4972,#2d6db5);
  border:3px solid white; color:white;
  font-size:20px; font-weight:800;
  display:flex; align-items:center; justify-content:center;
}
.online-dot {
  position:absolute; bottom:2px; right:2px;
  width:14px; height:14px; border-radius:50%; background:#10b981;
  border:2px solid white;
}
.profile-name { text-align:center; font-size:15px; font-weight:700; color:#1e293b; }
.profile-role { text-align:center; font-size:11px; color:#94a3b8; text-transform:capitalize; margin:3px 0 16px; }

.profile-info-rows { display:flex; flex-direction:column; gap:8px; }
.info-row { display:flex; align-items:center; gap:10px; padding:8px 10px; background:#f8fafc; border-radius:10px; }
.info-icon { font-size:14px; flex-shrink:0; }
.info-val  { font-size:12px; color:#475569; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* Forms column */
.forms-col { display:flex; flex-direction:column; gap:16px; }

.form-card { background:white; border-radius:20px; border:1px solid #e8edf2; overflow:hidden; }

.form-card-head {
  display:flex; align-items:center; gap:12px;
  padding:16px 20px; border-bottom:1px solid #f1f5f9;
}
.fch-icon { font-size:22px; }
.fch-title { font-size:14px; font-weight:700; color:#1e293b; }
.fch-sub   { font-size:12px; color:#94a3b8; margin-top:2px; }

.form-body { padding:20px; display:flex; flex-direction:column; gap:14px; }

.field-row { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
.field-row .field:only-child { grid-column:span 3; }
@media(max-width:600px){ .field-row{ grid-template-columns:1fr; } }

.field { display:flex; flex-direction:column; gap:5px; }
.field-label { font-size:11px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.05em; }
.req { color:#ef4444; } .opt { color:#94a3b8; font-weight:400; text-transform:none; }
.field-input {
  padding:10px 14px; background:#f8fafc; border:1.5px solid #e2e8f0;
  border-radius:12px; font-size:13px; color:#334155; outline:none;
  transition:border-color .15s, background .15s;
}
.field-input:focus { border-color:#1a4972; background:white; box-shadow:0 0 0 3px rgba(26,73,114,.07); }
.field-input--err  { border-color:#f87171 !important; }
.field-err { font-size:11px; color:#ef4444; }

.pw-field { position:relative; }
.pw-field .field-input { padding-right:40px; width:100%; }
.eye-btn {
  position:absolute; right:10px; top:50%; transform:translateY(-50%);
  background:none; border:none; cursor:pointer; font-size:14px; opacity:.6;
  transition:opacity .15s;
}
.eye-btn:hover { opacity:1; }

/* Strength */
.pw-strength { display:flex; align-items:center; gap:10px; }
.pw-bars { display:flex; gap:4px; flex:1; }
.pw-bar { height:4px; flex:1; border-radius:99px; transition:background .3s; }
.pw-label { font-size:11px; font-weight:700; }

/* Tips */
.pw-tips { display:grid; grid-template-columns:1fr 1fr; gap:6px; }
.pw-tip {
  display:flex; align-items:center; gap:8px;
  font-size:12px; color:#94a3b8; transition:color .2s;
}
.pw-tip--met { color:#059669; }
.pw-tip-dot {
  width:18px; height:18px; border-radius:50%; flex-shrink:0;
  background:#f1f5f9; display:flex; align-items:center; justify-content:center;
  font-size:10px; font-weight:700; transition:background .2s;
}
.pw-tip--met .pw-tip-dot { background:rgba(5,150,105,.12); }

.form-footer { display:flex; justify-content:flex-end; margin-top:4px; }

/* Buttons */
.btn {
  display:inline-flex; align-items:center; gap:8px;
  padding:10px 22px; border-radius:12px; border:none;
  font-size:13px; font-weight:700; cursor:pointer;
  transition:opacity .15s, transform .1s;
}
.btn:hover { opacity:.9; }
.btn:active { transform:scale(.98); }
.btn:disabled { opacity:.55; cursor:not-allowed; }
.btn--primary {
  background:linear-gradient(135deg,#1a4972,#0f2f4a);
  color:white; box-shadow:0 4px 14px rgba(26,73,114,.3);
}

/* Spinner */
.spin-ico { animation:spin 1s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }

.form-loading {
  display:flex; align-items:center; gap:12px;
  padding:32px 20px; color:#94a3b8; font-size:13px; justify-content:center;
}
.spin-lg {
  width:24px; height:24px; border-radius:50%;
  border:2.5px solid #e2e8f0; border-top-color:#1a4972;
  animation:spin 1s linear infinite;
}
</style>