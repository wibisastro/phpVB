<template>
    <nav aria-label="Page Navigation" v-if="isActive" :data-test="`pagination${'-' + instance}`">
        <ul class="pagination float-right">
            <li class="page-item"
                :class="{'disabled': firstPageOn}"
                v-if="pages > 1">
                <a class="page-link" @click="prev()"><i class="fa fa-chevron-left"></i> </a>
            </li>

            <li v-if="pages > pageInterval && !(intervalWindow-1<1)" class="page-item">
                <a class="page-link" @click="scrollPrev()"><i class="fa fa-ellipsis-h"></i></a>
            </li>

            <li class="page-item" v-for="page in pages"
                v-if="toggleVisibility(page + firstPage)"
                :class="{'active': currentPage === page + firstPage - 1}">

                <a class="page-link" @click="gotoPage(page + firstPage - 1)">
                    {{ page + firstPage - 1 }}
                </a>

            </li>

            <li class="page-item"
                v-if="pages > pageInterval && !((intervalWindow+1)*pageInterval>lastPage)">
                <a class="page-link" @click="scrollNext()"><i class="fa fa-ellipsis-h"></i></a>
            </li>

            <li class="page-item"
                :class="{'disabled': lastPageOn } "
                v-if="pages > 1">
                <a class="page-link" @click="next()"><i class="fa fa-chevron-right"></i> </a>
            </li>
        </ul>
    </nav>
</template>

<script>
    module.exports = {
        name: "gov2pagination-bs4",
        props: {
            isActive: Boolean,
            records: Number,
            itemPerPage: Number,
            scrollInterval: Number,
            instance: {
                type: String,
                default: ''
            }
        },
        computed: {
            scrolls: function () {
                let totalScroll = Math.floor(this.totalRecord / this.scrollInterval);
                if (this.totalRecord % this.scrollInterval) { totalScroll++; }
                return totalScroll;
            },
            pages: function () {
                let totalPage = Math.floor(this.records / this.itemPerPage);
                if (this.records % this.itemPerPage > 0) {totalPage++;}
                return totalPage;
            },
            lastPage: function () {
                return this.firstPage + this.pages - 1;
            },
            firstPageOn: function () {
                return this.currentPage === this.firstPage;
            },
            lastPageOn: function () {
                return this.currentPage === this.lastPage;
            },
            maxInterval: function () {
                return this.intervalWindow * this.pageInterval + 1;
            },
            minInterval: function () {
                return this.maxInterval - this.pageInterval + 1;
            }
        },
        methods: {
            setFirstPage(data) {
                this.firstPage = data;
            },
            setTotalRecord(data) {
                this.totalRecord = data['totalRecord'];
                eventBus.$emit(`setScroll`, this.scrolls);
            },
            gotoPage(page) {
                this.currentPage = page;
                eventBus.$emit(`changepage${this.instance}`, page);
            },
            prev() {
                if (this.currentPage > 1) {
                    this.currentPage -= 1;
                    this.gotoPage(this.currentPage);
                }
            },
            next() {
                if (this.currentPage < this.pages) {
                    this.currentPage += 1;
                    this.gotoPage(this.currentPage);
                }
            },
            toggleVisibility(page) {
                // if (this.pages <= this.pageInterval) {
                //     return true
                // }
                // else if (page > this.firstPage && page < this.lastPage) {
                //     if (page >= this.minInterval && page <= this.maxInterval) {
                //         return  true;
                //     }
                // }
                if (this.pages <= this.pageInterval) {
                    return true
                }
                else if (page >= this.minInterval && page <= this.maxInterval) {
                    return  true;
                }
                return false;
            },
            scrollPrev: function () {
                this.intervalWindow = this.intervalWindow - 1;
            },
            scrollNext: function () {
                this.intervalWindow = this.intervalWindow + 1;
            }
        },
        data: function () {
            return {
                currentPage: 1,
                pageInterval: 3,
                intervalWindow: 1,
                totalRecord: 10,
                firstPage: 1
            }
        },
        created: function () {
            eventBus.$on('setCurrentPage', this.gotoPage);
            eventBus.$on('setTotalRecord'+this.instance, this.setTotalRecord);
        }
    }
</script>

<style scoped>
    li a {
        cursor: pointer;
    }
</style>