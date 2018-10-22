<template>
<nav class="pagination is-right" role="navigation" aria-label="pagination">
  <!--span class="pagination-previous">Prev</span>
<span class="pagination-next">Next</span-->
  <ul class="pagination-list">
    <li v-if="pages > pageInterval">
      <a :disabled="firstPageOn" :class="{ 'is-current': currentPage === firstPage }" class="pagination-link" @click="gotoPage(firstPage)">{{ firstPage }}</a>
    </li>
    <li v-if="pages > pageInterval">
      <a class="pagination-link" @click="scrollPrev()" :disabled="intervalWindow-1<1">&lt;</a>
    </li>
    <li v-if="pages > pageInterval">
      <span class="pagination-ellipsis">&hellip;</span>
    </li>
    <li v-for="page in pages" v-if="toggleVisibility(page + firstPage - 1)">
      <a class="pagination-link" :class="{ 'is-current': currentPage === page + firstPage - 1 }"  @click="gotoPage(page + firstPage - 1)">{{ page + firstPage - 1 }}</a>
    </li>
    <li v-if="pages > pageInterval">
      <span class="pagination-ellipsis">&hellip;</span>
    </li>
    <li v-if="pages > pageInterval">
      <a class="pagination-link" @click="scrollNext()" :disabled="(intervalWindow+1)*pageInterval>lastPage">&gt;</a>
    </li>
    <li v-if="pages > pageInterval">
      <a :disabled="lastPageOn" :class="{ 'is-current': currentPage === lastPage }" class="pagination-link" @click="gotoPage(lastPage)">{{ lastPage }}</a>
    </li>
  </ul>
</nav>
</template>

<script>
module.exports = {
  name: 'gov2pagination',
  props: {
    instance: String,
    records: Number,
    itemPerPage: Number,
    scrollInterval: Number
  },
  computed: {
    scrolls: function () {
        var totalScroll=Math.floor(this.totalRecord / this.scrollInterval);
        var mod=this.totalRecord % this.scrollInterval;
        if (mod) {totalScroll++;}
        return totalScroll; 
    },
    pages: function () {
        var totalPage=Math.floor(this.records / this.itemPerPage);
        var mod=this.records % this.itemPerPage;
        if (mod) {totalPage++;}
        return totalPage; 
    },
    lastPage: function () {
        return this.firstPage+this.pages-1;
    },
    firstPageOn: function () {
        this.currentPage === this.firstPage;
    },
    lastPageOn: function () {
        this.currentPage === this.lastPage;
    },
    maxInterval: function () {
        return this.intervalWindow*this.pageInterval+1;
    },
    minInterval: function () {
        return this.maxInterval-this.pageInterval+1;
    }
  },
    methods: {
        setFirstPage(data) {
            this.firstPage=data;
        },
        setTotalRecord(data) {
            this.totalRecord=data['totalRecord'];
            eventBus.$emit('setScroll',this.scrolls);
        },
        gotoPage(page) {
            this.currentPage=page;
            eventBus.$emit('changepage',page);
        },
        scrollPrev() {
            this.intervalWindow=this.intervalWindow-1;
        },
        scrollNext() {
            this.intervalWindow=this.intervalWindow+1;
        },
        toggleVisibility(page) {
            if (this.pages <= this.pageInterval) {
                return true;
            } else if (page > this.firstPage && page < this.lastPage) {
                if (page >= this.minInterval && page <= this.maxInterval) {
                    return true;
                }
            }
        }
    },
    data: function () {
      return {
          currentPage: 1,
          pageInterval: 3,
          intervalWindow:1,
          totalRecord: 10,
          firstPage: 1
      }
    },
    created: function () {
        eventBus.$on('setCurrentPage', this.gotoPage);
        eventBus.$on('setTotalRecord', this.setTotalRecord);
    }
}
</script>

<style>

</style>