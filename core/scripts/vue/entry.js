import Component from './app/__FILE__';
import nestedDraggable from "./infra/nested";
// import { PieChart } from 'vue-chartjs'

Vue.component(Component.name, Component);
Vue.component(nestedDraggable.name, nestedDraggable);