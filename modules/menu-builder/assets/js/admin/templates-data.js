/**
 * CGS MB Admin — Ready templates data (v4.10.90)
 */
(function (w) {
  'use strict';
var CGS_READY_TPL = {
    digikala: {
      layout: 'mega-sidebar', mega_cols: 4,
      items: [
        { id:'dk_root', label:'دسته‌بندی کالاها', url:'#', icon:'☰', content_type:'link', children: [
          { id:'dk_cat_digital', label:'کالای دیجیتال', url:'#', icon:'📱', content_type:'link', children: [
            { id:'dk_h1', label:'لوازم جانبی گوشی', content_type:'heading', children: [
              { id:'dk_h1a', label:'کیف و کاور گوشی', content_type:'link', url:'#' },
              { id:'dk_h1b', label:'پاور بانک (شارژ همراه)', content_type:'link', url:'#' },
              { id:'dk_h1c', label:'پایه نگهدارنده گوشی', content_type:'link', url:'#' },
              { id:'dk_h1d', label:'محافظ صفحه نمایش گوشی', content_type:'link', url:'#' },
              { id:'dk_h1e', label:'کابل و مبدل', content_type:'link', url:'#' }
            ]},
            { id:'dk_h2', label:'گوشی موبایل', content_type:'heading', children: [
              { id:'dk_h2a', label:'سامسونگ', content_type:'link', url:'#' },
              { id:'dk_h2b', label:'شیائومی', content_type:'link', url:'#' },
              { id:'dk_h2c', label:'اپل', content_type:'link', url:'#' },
              { id:'dk_h2d', label:'هوآوی', content_type:'link', url:'#' },
              { id:'dk_h2e', label:'نوکیا', content_type:'link', url:'#' }
            ]},
            { id:'dk_h3', label:'هدفون و هدست', content_type:'heading', children: [
              { id:'dk_h3a', label:'هدفون بی‌سیم', content_type:'link', url:'#' },
              { id:'dk_h3b', label:'هندزفری', content_type:'link', url:'#' },
              { id:'dk_h3c', label:'اسپیکر بلوتوث', content_type:'link', url:'#' }
            ]},
            { id:'dk_h4', label:'تبلت', content_type:'heading', children: [
              { id:'dk_h4a', label:'تبلت اندروید', content_type:'link', url:'#' },
              { id:'dk_h4b', label:'آیپد', content_type:'link', url:'#' },
              { id:'dk_h4c', label:'کتابخوان', content_type:'link', url:'#' }
            ]},
            { id:'dk_promo', label:'بنر ویژه', content_type:'image', url:'#', image:'' },
            { id:'dk_brands', label:'برندها', content_type:'row', children: [
              { id:'dk_br1', label:'SAMSUNG', content_type:'brand', url:'#' },
              { id:'dk_br2', label:'HONOR', content_type:'brand', url:'#' },
              { id:'dk_br3', label:'HUAWEI', content_type:'brand', url:'#' }
            ]}
          ]},
          { id:'dk_cat_car', label:'خودرو، ابزار و تجهیزات صنعتی', url:'#', icon:'🚗', content_type:'link', children: [
            { id:'dk_c1', label:'لوازم خودرو', content_type:'heading', children: [
              { id:'dk_c1a', label:'روغن موتور', content_type:'link', url:'#' },
              { id:'dk_c1b', label:'لاستیک', content_type:'link', url:'#' },
              { id:'dk_c1c', label:'باتری', content_type:'link', url:'#' }
            ]},
            { id:'dk_c2', label:'ابزار برقی', content_type:'heading', children: [
              { id:'dk_c2a', label:'دریل', content_type:'link', url:'#' },
              { id:'dk_c2b', label:'فرز', content_type:'link', url:'#' }
            ]}
          ]},
          { id:'dk_cat_fashion', label:'مد و پوشاک', url:'#', icon:'👕', content_type:'link', children: [
            { id:'dk_f1', label:'مردانه', content_type:'heading', children: [
              { id:'dk_f1a', label:'پیراهن', content_type:'link', url:'#' },
              { id:'dk_f1b', label:'شلوار', content_type:'link', url:'#' }
            ]},
            { id:'dk_f2', label:'زنانه', content_type:'heading', children: [
              { id:'dk_f2a', label:'مانتو', content_type:'link', url:'#' },
              { id:'dk_f2b', label:'کیف', content_type:'link', url:'#' }
            ]}
          ]},
          { id:'dk_cat_home', label:'خانه و آشپزخانه', url:'#', icon:'🏠', content_type:'link', children: [
            { id:'dk_hm1', label:'لوازم آشپزخانه', content_type:'heading', children: [
              { id:'dk_hm1a', label:'قابلمه', content_type:'link', url:'#' },
              { id:'dk_hm1b', label:'ظروف', content_type:'link', url:'#' }
            ]}
          ]},
          { id:'dk_cat_beauty', label:'زیبایی و سلامت', url:'#', icon:'💄', content_type:'link', children: [
            { id:'dk_b1', label:'آرایشی', content_type:'heading', children: [
              { id:'dk_b1a', label:'کرم', content_type:'link', url:'#' },
              { id:'dk_b1b', label:'عطر', content_type:'link', url:'#' }
            ]}
          ]}
        ]},
        { id:'dk_home', label:'صفحه اصلی', url:'#', icon:'🏠', content_type:'link', children: [] },
        { id:'dk_super', label:'سوپرمارکت', url:'#', icon:'🛒', content_type:'link', children: [] },
        { id:'dk_offer', label:'شگفت‌انگیز', url:'#', icon:'🔥', badge:'SALE', content_type:'link', children: [] }
      ]
    },
   
    
    
    
    fashion_dept_mega: { layout: 'mega-sidebar', mega_cols: 4, items: [
      { id:'fd_root', label:'Shop', url:'#', icon:'☰', content_type:'link', children: [
        { id:'fd_side', label:'All Departments', url:'#', content_type:'link', children: [
          { id:'fd_shoes', label:'Shoes', content_type:'heading', children: [
            { id:'fd_sh1', label:'Classic', content_type:'link', url:'#' },
            { id:'fd_sh2', label:'Best Sellers', content_type:'link', url:'#' },
            { id:'fd_sh3', label:'Boots', content_type:'link', url:'#' }
          ]},
          { id:'fd_cloth', label:'Clothing', content_type:'heading', children: [
            { id:'fd_cl1', label:'Tees', content_type:'link', url:'#' },
            { id:'fd_cl2', label:'Hoodies', content_type:'link', url:'#' }
          ]},
          { id:'fd_sport', label:'Sports', content_type:'heading', children: [
            { id:'fd_sp1', label:'Running', content_type:'link', url:'#' },
            { id:'fd_sp2', label:'Basketball', content_type:'link', url:'#' }
          ]}
        ]}
      ]},
      { id:'fd_home', label:'Home', url:'#', content_type:'link', children: [] },
      { id:'fd_women', label:'Women', url:'#', content_type:'link', children: [] },
      { id:'fd_men', label:'Men', url:'#', content_type:'link', children: [] }
    ]},
    product_boards_mega: { layout: 'mega-products', mega_cols: 4, items: [
      { id:'pb_boards', label:'Boards', url:'#', icon:'🛹', content_type:'link', children: [
        { id:'pb_live', label:'Featured', content_type:'woo_products', limit:4, orderby:'date', children: [] },
        { id:'pb_card1', label:'Mohawk', content_type:'product_card', url:'#', price:'$89', children: [] },
        { id:'pb_card2', label:'Dodge', content_type:'product_card', url:'#', price:'$99', children: [] },
        { id:'pb_cta', label:'Start Riding Today', content_type:'hub_card', icon:'🚀', desc:'Brand Finder · Complete Boards', link_label:'View All →', url:'#' }
      ]},
      { id:'pb_acc', label:'Accessories', url:'#', content_type:'link', children: [] }
    ]},

    hubspot_platform: { layout: 'mega-content', mega_cols: 4, items: [
      { id:'hs_products', label:'Products', url:'#', content_type:'link', children: [
        { id:'hs_mkt', label:'Marketing Hub', icon:'📣', desc:'Marketing automation software', link_label:'Free and premium plans', content_type:'hub_card', url:'#' },
        { id:'hs_sales', label:'Sales Hub', icon:'📞', desc:'Sales software', link_label:'Free and premium plans', content_type:'hub_card', url:'#' },
        { id:'hs_svc', label:'Service Hub', icon:'💬', desc:'Customer service software', link_label:'Free and premium plans', content_type:'hub_card', url:'#' },
        { id:'hs_cnt', label:'Content Hub', icon:'📄', desc:'Content marketing software', link_label:'Free and premium plans', content_type:'hub_card', url:'#' },
        { id:'hs_ops', label:'Operations Hub', icon:'⚙️', desc:'Operations software', link_label:'Free and premium plans', content_type:'hub_card', url:'#' },
        { id:'hs_com', label:'Commerce Hub', icon:'🛒', desc:'B2B commerce software', link_label:'Free and premium plans', content_type:'hub_card', url:'#' },
        { id:'hs_crm', label:'Smart CRM', icon:'🧡', desc:'AI-powered CRM software', link_label:'Learn more', content_type:'hub_card', url:'#' },
        { id:'hs_smb', label:'Small Business Bundle', icon:'🏢', desc:'Starter edition', link_label:'Learn more', content_type:'hub_card', url:'#' }
      ]},
      { id:'hs_sol', label:'Solutions', url:'#', content_type:'link', children: [] },
      { id:'hs_price', label:'Pricing', url:'#', content_type:'link', children: [] }
    ]},
    adidas_mega: { layout: 'mega-content', mega_cols: 6, items: [
      { id:'ad_men', label:'MEN', url:'#', content_type:'link', children: [
        { id:'ad_new', label:"WHAT'S NEW?", content_type:'heading', children: [
          { id:'ad_n1', label:'New Arrivals', content_type:'link', url:'#' },
          { id:'ad_n2', label:'Best Sellers', content_type:'link', url:'#' },
          { id:'ad_n3', label:'Stories', content_type:'link', url:'#' }
        ]},
        { id:'ad_colab', label:'COLLABORATIONS', content_type:'heading', children: [
          { id:'ad_c1', label:'IVY PARK', content_type:'link', url:'#' },
          { id:'ad_c2', label:'YEEZY', content_type:'link', url:'#' },
          { id:'ad_c3', label:'Prada', content_type:'link', url:'#' }
        ]},
        { id:'ad_sports', label:'SPORTS', content_type:'heading', children: [
          { id:'ad_s1', label:'Football', content_type:'link', url:'#' },
          { id:'ad_s2', label:'Running', content_type:'link', url:'#' },
          { id:'ad_s3', label:'Basketball', content_type:'link', url:'#' }
        ]},
        { id:'ad_orig', label:'ORIGINALS', content_type:'heading', children: [
          { id:'ad_o1', label:'Superstar', content_type:'link', url:'#' },
          { id:'ad_o2', label:'Samba', content_type:'link', url:'#' }
        ]},
        { id:'ad_coll', label:'COLLECTIONS', content_type:'heading', children: [
          { id:'ad_cl1', label:'Ultraboost', content_type:'link', url:'#' },
          { id:'ad_cl2', label:'NMD', content_type:'link', url:'#' }
        ]}
      ]},
      { id:'ad_women', label:'WOMEN', url:'#', content_type:'link', children: [] },
      { id:'ad_kids', label:'KIDS', url:'#', content_type:'link', children: [] },
      { id:'ad_sale', label:'SALE', url:'#', badge:'HOT', content_type:'link', children: [] }
    ]},

    woo_shop_mega: {
      layout: 'mega-products', mega_cols: 4,
      items: [
        { id:'woo_shop', label:'SHOP', url:'#', icon:'🛒', badge:'SALE', content_type:'link', children: [
          { id:'woo_latest', label:'LATEST PRODUCTS', content_type:'heading', children: [
            { id:'woo_lp1', label:'Woo Ninja — $0.00', content_type:'link', url:'#' },
            { id:'woo_lp2', label:'Mega Posters — $18.00', content_type:'link', url:'#' },
            { id:'woo_lp3', label:'Premium Hoodie — $29.00', content_type:'link', url:'#' }
          ]},
          { id:'woo_prods', label:'PRODUCTS', content_type:'heading', children: [
            { id:'woo_p1', label:'Woo Single #2 — $3.00', content_type:'link', url:'#' },
            { id:'woo_p2', label:'Woo Album #4 — $9.00', content_type:'link', url:'#' },
            { id:'woo_p3', label:'Woo Single #1 — $3.00', content_type:'link', url:'#' }
          ]},
          { id:'woo_rev', label:'RECENT REVIEWS', content_type:'heading', children: [
            { id:'woo_r1', label:'Woo Ninja — Rated 4/5 by Maria', content_type:'link', url:'#' },
            { id:'woo_r2', label:'Premium Quality — Rated 5/5 by Maria', content_type:'link', url:'#' }
          ]},
          { id:'woo_cat', label:'PRODUCT CATEGORIES', content_type:'heading', children: [
            { id:'woo_c1', label:'Exclusive Clothing', content_type:'link', url:'#' },
            { id:'woo_c2', label:'Stylish Hoodies', content_type:'link', url:'#' },
            { id:'woo_c3', label:'Unique T-Shirts', content_type:'link', url:'#' },
            { id:'woo_c4', label:'Mega Posters', content_type:'link', url:'#' }
          ]},
          { id:'woo_banner', label:'SHOP HERO IMAGE', content_type:'image', url:'#', image:'' }
        ]},
        { id:'woo_std', label:'STANDARD', url:'#', content_type:'link', children: [] },
        { id:'woo_mega', label:'MEGA ITEMS', url:'#', content_type:'link', children: [] },
        { id:'woo_dd', label:'DROPDOWN', url:'#', content_type:'link', children: [] },
        { id:'woo_tabs', label:'TABS', url:'#', badge:'HOT', content_type:'link', children: [] },
        { id:'woo_feat', label:'FEATURES', url:'#', content_type:'link', children: [] },
        { id:'woo_contact', label:'CONTACT US', url:'#', content_type:'link', children: [] }
      ]
    },

    wp_mega_classic: {
      layout: 'mega-content', mega_cols: 4,
      items: [
        { id:'wpm_mega', label:'MEGA ITEMS', url:'#', icon:'▦', content_type:'link', children: [
          { id:'wpm_c1', label:'LAYOUTS BUILDER', content_type:'heading', children: [
            { id:'wpm_c1a', label:'Advanced Feature', content_type:'link', url:'#' },
            { id:'wpm_c1b', label:'Potential Menus', content_type:'link', url:'#' },
            { id:'wpm_c1c', label:'High-Quality Mega', content_type:'link', url:'#' }
          ]},
          { id:'wpm_c2', label:'EASY CUSTOMIZATION', content_type:'heading', children: [
            { id:'wpm_c2a', label:'Grained Control', content_type:'link', url:'#' },
            { id:'wpm_c2b', label:'Easy Integration', content_type:'link', url:'#' },
            { id:'wpm_c2c', label:'Interactive Elements', content_type:'link', url:'#' }
          ]},
          { id:'wpm_c3', label:'TABBED SUBMENU', content_type:'heading', children: [
            { id:'wpm_c3a', label:'Priority Support', content_type:'link', url:'#' },
            { id:'wpm_c3b', label:'Easy Drag & Drop', content_type:'link', url:'#' },
            { id:'wpm_c3c', label:'Animation Options', content_type:'link', url:'#' }
          ]},
          { id:'wpm_c4', label:'MENU ANIMATION', content_type:'heading', children: [
            { id:'wpm_c4a', label:'Styling Menus', content_type:'link', url:'#' },
            { id:'wpm_c4b', label:'Styling Options', content_type:'link', url:'#' },
            { id:'wpm_c4c', label:'Advanced Interactive', content_type:'link', url:'#' }
          ]},
          { id:'wpm_posts', label:'POSTS CAROUSEL', content_type:'heading', children: [
            { id:'wpm_pc1', label:'esk Impor — Milan Fashion', content_type:'link', url:'#' },
            { id:'wpm_pc2', label:'Summer Collection 2026', content_type:'link', url:'#' }
          ]},
          { id:'wpm_recent', label:'RECENT POSTS', content_type:'heading', children: [
            { id:'wpm_rp1', label:'Milan Fashion Week Import', content_type:'link', url:'#' },
            { id:'wpm_rp2', label:'Eiget Fels Nec: Puru Commo', content_type:'link', url:'#' },
            { id:'wpm_rp3', label:'Scenes The Victoria Secret', content_type:'link', url:'#' }
          ]},
          { id:'wpm_tags', label:'TAG CLOUDS', content_type:'heading', children: [
            { id:'wpm_t1', label:'Animations', content_type:'link', url:'#' },
            { id:'wpm_t2', label:'Builder', content_type:'link', url:'#' },
            { id:'wpm_t3', label:'Customize', content_type:'link', url:'#' },
            { id:'wpm_t4', label:'Drag', content_type:'link', url:'#' },
            { id:'wpm_t5', label:'Dropdown', content_type:'link', url:'#' },
            { id:'wpm_t6', label:'Features', content_type:'link', url:'#' },
            { id:'wpm_t7', label:'Mega Menu', content_type:'link', url:'#' },
            { id:'wpm_t8', label:'Widgets', content_type:'link', url:'#' }
          ]},
          { id:'wpm_ad', label:'IMAGE WIDGET — AD BANNER', content_type:'image', url:'#', image:'' }
        ]},
        { id:'wpm_std', label:'STANDARD', url:'#', content_type:'link', children: [
          { id:'wpm_std1', label:'Menu Item 1', content_type:'link', url:'#' },
          { id:'wpm_std2', label:'Menu Item 2', content_type:'link', url:'#' }
        ]},
        { id:'wpm_dd', label:'DROPDOWN', url:'#', content_type:'link', children: [
          { id:'wpm_dd1', label:'Dropdown A', content_type:'link', url:'#' },
          { id:'wpm_dd2', label:'Dropdown B', content_type:'link', url:'#' }
        ]},
        { id:'wpm_tabs', label:'TABS', url:'#', badge:'HOT', content_type:'link', children: [] },
        { id:'wpm_feat', label:'FEATURES', url:'#', content_type:'link', children: [] },
        { id:'wpm_shop', label:'SHOP', url:'#', content_type:'link', children: [] },
        { id:'wpm_contact', label:'CONTACT US', url:'#', content_type:'link', children: [] }
      ]
    },

    hero_content: {
      layout: 'mega-content', mega_cols: 4,
      items: [{ id:'hr1', label:'Demo', icon:'⭐', children: [
        { id:'hr1a', label:'Blog posts', content_type:'heading', children: [
          { id:'hr1a1', label:'Not my dog', content_type:'card', desc:'خلاصه پست' },
          { id:'hr1a2', label:'City nights', content_type:'card', desc:'خلاصه' }
        ]},
        { id:'hr1b', label:'Image Heading', content_type:'image', desc:'تصویر بزرگ' },
        { id:'hr1c', label:'List', content_type:'heading', children: [
          { id:'hr1c1', label:'About us', icon:'ℹ️', content_type:'link' },
          { id:'hr1c2', label:'Support', icon:'💬', content_type:'link' }
        ]},
        { id:'hr1d', label:'Post', content_type:'heading', children: [
          { id:'hr1d1', label:'My sweet life', content_type:'card', desc:'متن کوتاه' }
        ]}
      ]}]
    },
    shop_products: {
      layout: 'mega-products', mega_cols: 4,
      items: [{ id:'sh1', label:'SHOP', icon:'🛍️', children: [
        { id:'sh1a', label:'NEW ARRIVAL', content_type:'heading', badge:'NEW', children: [
          { id:'sh1a1', label:'Casual Tops', content_type:'link' },
          { id:'sh1a2', label:'Tunics', content_type:'link' }
        ]},
        { id:'sh1b', label:'EDITORIAL', content_type:'heading', children: [
          { id:'sh1b1', label:'Vests', content_type:'link' }
        ]},
        { id:'sh1c', label:'اسلایدر محصول', content_type:'product_slider', products: [
          { title:'کفش ورزشی', price:'۲٬۸۰۰٬۰۰۰', badge:'SALE' },
          { title:'کیف', price:'۱٬۵۰۰٬۰۰۰' },
          { title:'تی‌شرت', price:'۴۵۰٬۰۰۰', badge:'NEW' }
        ]},
        { id:'sh1d', label:'BEST SELLER', content_type:'heading', children: [
          { id:'sh1d1', label:'Product A', content_type:'card', desc:'150.00', badge:'HOT' },
          { id:'sh1d2', label:'Product B', content_type:'card', desc:'150.00' }
        ]}
      ]}]
    },
    brands_grid: {
      layout: 'mega-brands', mega_cols: 5,
      items: [{ id:'br0', label:'برندها', icon:'🏷️', children: [
        { id:'br1', label:'Apple', content_type:'brand' },
        { id:'br2', label:'Samsung', content_type:'brand' },
        { id:'br3', label:'Sony', content_type:'brand' },
        { id:'br4', label:'Microsoft', content_type:'brand' },
        { id:'br5', label:'Intel', content_type:'brand' },
        { id:'br6', label:'LG', content_type:'brand' },
        { id:'br7', label:'Huawei', content_type:'brand' },
        { id:'br8', label:'Xiaomi', content_type:'brand' }
      ]}]
    },
    news_magazine: {
      layout: 'mega-content', mega_cols: 4,
      items: [{ id:'nw1', label:'FASHION', children: [
        { id:'nw1a', label:'بخش‌ها', content_type:'heading', children: [
          { id:'nw1a1', label:'All', content_type:'link' },
          { id:'nw1a2', label:'Street Fashion', content_type:'link' },
          { id:'nw1a3', label:'Vogue', content_type:'link' }
        ]},
        { id:'nw1b', label:'Fashion Outfit Ideas', content_type:'card', desc:'Aug 2019', badge:'Vogue' },
        { id:'nw1c', label:'Style Spy', content_type:'card', desc:'Aug 2019', badge:'Vogue' },
        { id:'nw1d', label:'Gala Night', content_type:'card', desc:'Aug 2019', badge:'Vogue' }
      ]}]
    },
    hub_cards: {
      layout: 'mega-content', mega_cols: 4,
      items: [{ id:'hb0', label:'Products', children: [
        { id:'hb1', label:'Marketing Hub', content_type:'card', desc:'اتوماسیون بازاریابی', icon:'📈' },
        { id:'hb2', label:'Sales Hub', content_type:'card', desc:'نرم‌افزار فروش', icon:'💼' },
        { id:'hb3', label:'Service Hub', content_type:'card', desc:'پشتیبانی', icon:'❤️' },
        { id:'hb4', label:'CMS Hub', content_type:'card', desc:'مدیریت محتوا', icon:'📝' }
      ]}]
    },
    tabs_panel: {
      layout: 'tabs', mega_cols: 3,
      items: [{ id:'tb0', label:'منوی تب‌دار', icon:'📑', children: [
        { id:'tb1', label:'Movies', content_type:'heading', icon:'🎬', children: [
          { id:'tb1a', label:'Best Sellers', content_type:'link' },
          { id:'tb1b', label:'Inception', content_type:'link' },
          { id:'tb1c', label:'Gravity', content_type:'card', desc:'Sci-Fi' }
        ]},
        { id:'tb2', label:'Books', content_type:'heading', icon:'📚', children: [
          { id:'tb2a', label:'Fiction', content_type:'link' },
          { id:'tb2b', label:'Business', content_type:'link' }
        ]},
        { id:'tb3', label:'Music', content_type:'heading', icon:'🎵', children: [
          { id:'tb3a', label:'Albums', content_type:'link' },
          { id:'tb3b', label:'Playlists', content_type:'link' }
        ]}
      ]}]
    }
  };
  w.CGS_READY_TPL = CGS_READY_TPL;
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};
  w.CGS_MB_Modules.templatesData = { READY_TPL: CGS_READY_TPL };
})(window);
