// src/router/index.js
import { createRouter, createWebHistory } from "vue-router";
import Login              from "@/pages/Authentication/Login.vue";
import Dashboard          from "@/pages/Dashboard.vue";
import AdminUserManagement from "@/pages/Admin/AdminUserManagement.vue";
import AuditTrail         from "@/pages/Admin/AuditTrail.vue";
import AccountPage        from "@/pages/AccountPage.vue";
import CaseMaster         from "@/pages/Admin/cases/CaseMaster.vue";
import CaseCategory       from "@/pages/Admin/MasterData/CasesCategory.vue";
import CourtMaster         from "@/pages/Admin/MasterData/CourtMaster.vue";
import Documents         from "@/pages/Admin/MasterData/Documents.vue";
const routes = [
  {
    path: "/",
    name: "Login",
    component: Login,
    meta: { guest: true },
  },
  {
    path: "/account",
    name: "AccountPage",
    component: AccountPage,
    meta: { requiresAuth: true },
  },

  {
    path: "/usermanagement",
    name: "AdminUserManagement",
    component: AdminUserManagement,
    meta: { requiresAuth: true, role: "admin" },
  },
  {
    path: "/courtmaster",
    name: "CourtMaster",
    component: CourtMaster,
    meta: { requiresAuth: true, role: "admin" },
  },
  {
    path: "/casecategory",
    name: "CaseCategory",
    component: CaseCategory,
    meta: { requiresAuth: true, role: "admin" },
  },
  {
    path: "/documents",
    name: "Documents",
    component: Documents,
    meta: { requiresAuth: true, role: "admin" },
  },
  {
    path: "/casemaster",
    name: "CaseMaster",
    component: CaseMaster,
    meta: { requiresAuth: true, role: "admin" },
  },
  {
    path: "/audittrail",
    name: "AuditTrail",
    component: AuditTrail,
    meta: { requiresAuth: true },
  },
  {
    path: "/dashboard",
    name: "Dashboard",
    component: Dashboard,
    meta: { requiresAuth: true },
  },
  {
    path: "/:pathMatch(.*)*",
    redirect: "/dashboard",
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// ── Helpers ───────────────────────────────────────────────────────

function isAuthenticated() {
  return !!sessionStorage.getItem("token");
}

function getUserRole() {
  try {
    const user = JSON.parse(sessionStorage.getItem("user"));
    // Handles both { role: { name: "admin" } } and { role: "Admin" }
    return user?.role?.name ?? (typeof user?.role === "string" ? user.role.toLowerCase() : null);
  } catch {
    return null;
  }
}

// ── Navigation Guard ──────────────────────────────────────────────
router.beforeEach((to, from, next) => {
  const authenticated = isAuthenticated();
  const userRole      = getUserRole();

  // Not logged in → redirect to login
  if (to.meta.requiresAuth && !authenticated) {
    return next("/");
  }

  // Already logged in → don't show login page
  if (to.meta.guest && authenticated) {
    return next("/dashboard");
  }

  // Route requires a specific role the user doesn't have
  if (to.meta.role && to.meta.role !== userRole) {
    return next("/dashboard");
  }

  next();
});

export default router;