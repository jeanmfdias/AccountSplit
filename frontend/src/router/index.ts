import { createRouter, createWebHistory } from 'vue-router'
import GroupsView from '../views/GroupsView.vue'
import GroupView from '../views/GroupView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'groups',
      component: GroupsView,
    },
    {
      path: '/groups/:id',
      name: 'group',
      component: GroupView,
    },
  ],
})

export default router