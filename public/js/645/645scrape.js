const puppeteer = require("puppeteer");
const mysql = require("mysql2/promise");
const axios = require("axios");
const getDbConnection = require("../db.js");
(async () => {
  // Kết nối database
  let db;
  try {
    db = await getDbConnection();
  } catch (err) {
    console.error("Lỗi kết nối DB:", err.message);
    return;
  }

  let browser;
  const maxRetries = 3;
  let retries = 0;

  try {
    while (retries < maxRetries) {
      try {
    console.log("🚀 Launching Puppeteer browser...");
    browser = await puppeteer.launch({
      headless: true,
      args: [
        "--no-sandbox",
        "--disable-setuid-sandbox",
        "--disable-dev-shm-usage",
        "--disable-gpu",
        "--disable-web-security",
        "--disable-features=VizDisplayCompositor",
        "--user-agent=Mozilla/5.0 (Linux; x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
      ],
    });
    console.log("✅ Browser launched successfully");

        const page = await browser.newPage();
        await page.setExtraHTTPHeaders({
          Accept:
            "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
          "Accept-Language": "en-US,en;q=0.5",
          Referer: "https://www.minhngoc.net.vn/",
        });

        console.log("🌐 Navigating to minhngoc.net.vn...");
        await page.goto(
          "https://www.minhngoc.net.vn/ket-qua-xo-so/dien-toan-vietlott/mega-6x45.html",
          {
            waitUntil: "networkidle2",
            timeout: 120000,
          }
        );
        console.log("✅ Page loaded successfully");

         // Đợi trang load xong và debug
         await new Promise((resolve) => setTimeout(resolve, 3000)); // Đợi 3 giây để JavaScript load xong
         
         // Debug: Lấy HTML để kiểm tra
         const pageContent = await page.content();
         console.log("📄 Page loaded, HTML length:", pageContent.length);
         console.log("🔍 Contains bangkq6x36:", pageContent.includes('bangkq6x36'));
         console.log("🔍 Contains boxkqxsdientoan:", pageContent.includes('boxkqxsdientoan'));
         
         // Tìm các element có sẵn
         const availableElements = await page.evaluate(() => {
           const elements = [];
           document.querySelectorAll('div[class*="bangkq"], div[class*="boxkq"]').forEach(el => {
             elements.push({
               className: el.className,
               text: el.textContent.substring(0, 100)
             });
           });
           return elements;
         });
         console.log("🔍 Available elements:", availableElements);

         await page.waitForSelector("div.bangkq6x36", { timeout: 30000 });

         const results = await page.evaluate(() => {
          const sections = document.querySelectorAll("div.bangkq6x36");
          const resultList = [];

          sections.forEach((section, index) => {
            const parent = section.closest("div.boxkqxsdientoan");
            if (!parent) return;

            // Parse từ text content của section
            const sectionText = section.textContent;
            
            // Debug: Test regex patterns
            console.log("🔍 Testing regex patterns...");
            
            // Extract draw number từ text (test different patterns)
            const drawNumberPatterns = [
              /Kỳ vé:\s*#(\d+)/,
              /Kỳ vé:\s*#(\d+)/,
              /#(\d+)/
            ];
            let drawNumber = null;
            for (const pattern of drawNumberPatterns) {
              const match = sectionText.match(pattern);
              if (match) {
                drawNumber = match[1].replace(/^0+/, "");
                console.log("✅ Draw number pattern matched:", pattern, "->", drawNumber);
                break;
              }
            }
            console.log("❌ Draw number not found");

            // Extract draw date từ text (test different patterns)
            const drawDatePatterns = [
              /\|\s*Ngày quay thưởng\s+(\d{2}\/\d{2}\/\d{4})/,
              /Ngày quay thưởng\s+(\d{2}\/\d{2}\/\d{4})/,
              /(\d{2}\/\d{2}\/\d{4})/
            ];
            let drawDate = null;
            for (const pattern of drawDatePatterns) {
              const match = sectionText.match(pattern);
              if (match) {
                drawDate = match[1].split("/").reverse().join("-");
                console.log("✅ Draw date pattern matched:", pattern, "->", drawDate);
                break;
              }
            }
            console.log("❌ Draw date not found");

             // Extract numbers từ text - chỉ lấy phần trước "Giải thưởng"
             // Thử nhiều cách tìm "Giải thưởng"
             const giaiThuongPatterns = ['Giải thưởng', 'Gi\u1ea3i th\u01b0\u1edfng', 'Giải', 'Gi\u1ea3i'];
             let giaiThuongIndex = -1;
             let usedPattern = '';
             
             for (const pattern of giaiThuongPatterns) {
               giaiThuongIndex = sectionText.indexOf(pattern);
               if (giaiThuongIndex !== -1) {
                 usedPattern = pattern;
                 break;
               }
             }
             
             const beforeGiaiThuong = giaiThuongIndex !== -1 ? 
               sectionText.substring(0, giaiThuongIndex).trim() : 
               sectionText.trim();
             
             // Tìm 6 số kết quả xổ số trong phần trước "Giải thưởng"
             // Pattern: tìm 6 số 2 chữ số liên tiếp với khoảng trắng ở cuối chuỗi
             const numbersMatch = beforeGiaiThuong.match(/(\d{2})\s+(\d{2})\s+(\d{2})\s+(\d{2})\s+(\d{2})\s+(\d{2})\s*$/);
             
             let numbers = null;
             let numbersDebug = {
               giaiThuongIndex: giaiThuongIndex,
               usedPattern: usedPattern,
               beforeGiaiThuong: beforeGiaiThuong.trim(),
               beforeGiaiThuongLength: beforeGiaiThuong.trim().length,
               numbersMatch: numbersMatch
             };
             
             if (numbersMatch) {
               // Lấy 6 số từ regex match
               const rawNumbers = [numbersMatch[1], numbersMatch[2], numbersMatch[3], numbersMatch[4], numbersMatch[5], numbersMatch[6]];
               numbersDebug.rawNumbers = rawNumbers;
               
               // Validate: chỉ chấp nhận số từ 01-45
               const validNumbers = rawNumbers.filter((num) => {
                 const n = parseInt(num);
                 return n >= 1 && n <= 45;
               });
               numbersDebug.validNumbers = validNumbers;
               
               if (validNumbers.length === 6) {
                 // Sắp xếp từ nhỏ đến lớn
                 numbers = validNumbers.sort((a, b) => parseInt(a) - parseInt(b));
                 numbersDebug.finalNumbers = numbers;
               }
             }

            // Extract jackpot từ section text (thay vì parent text)
            const sectionTextForJackpot = section.textContent;
            let jackpot = null;
            
            // Thử tìm jackpot từ section text trước
            const jackpotMatch = sectionTextForJackpot.match(/Jackpot: ([\d,\.]+)đ/);
            if (jackpotMatch) {
              jackpot = jackpotMatch[1].replace(/[,\.]/g, "");
            } else {
              // Fallback: tìm từ DOM element
              const jackpotElement = parent.querySelector("b#DT6X45_G_JACKPOT");
              if (jackpotElement) {
                jackpot = jackpotElement.innerText.replace(/[,\.\s]/g, "").replace("đ", "");
              }
            }

            // Cào số lượng giải bằng DOM selectors (từ 645demo.js)
            const jackpot1Sl = parent
              .querySelector("td#DT6X45_S_JACKPOT")
              ?.innerText.replace(/,/g, "");
            const giaiNhatSl = parent
              .querySelector("td#DT6X45_S_G1")
              ?.innerText.replace(/,/g, "");
            const giaiNhiSl = parent
              .querySelector("td#DT6X45_S_G2")
              ?.innerText.replace(/,/g, "");
            const giaiBaSl = parent
              .querySelector("td#DT6X45_S_G3")
              ?.innerText.replace(/,/g, "");

            // Cào giá trị giải bằng DOM selectors (từ 645demo.js)
            // Thử nhiều cách khác nhau để tìm giá trị giải thưởng
            let giaiNhatGiaTri = "";
            let giaiNhiGiaTri = "";
            let giaiBaGiaTri = "";

            // Cách 1: Tìm theo selector từ 645demo.js
            const giaiThuongElements = parent.querySelectorAll("td.giai_thuong_gia_tri b");
            if (giaiThuongElements.length >= 4) {
              giaiNhatGiaTri = giaiThuongElements[1]?.innerText.replace(/[,\.\s]/g, "").replace("đ", "") || "";
              giaiNhiGiaTri = giaiThuongElements[2]?.innerText.replace(/[,\.\s]/g, "").replace("đ", "") || "";
              giaiBaGiaTri = giaiThuongElements[3]?.innerText.replace(/[,\.\s]/g, "").replace("đ", "") || "";
            }

            // Cách 2: Nếu không tìm thấy, thử tìm theo pattern khác
            if (!giaiNhatGiaTri || giaiNhatGiaTri === "đ") {
              const parentText = parent.textContent;
              const giaiNhatMatch = parentText.match(/Giải nhất[:\s]*([\d,\.]+)đ/i);
              if (giaiNhatMatch) {
                giaiNhatGiaTri = giaiNhatMatch[1].replace(/[,\.]/g, "");
              }
            }

            if (!giaiNhiGiaTri || giaiNhiGiaTri === "đ") {
              const parentText = parent.textContent;
              const giaiNhiMatch = parentText.match(/Giải nhì[:\s]*([\d,\.]+)đ/i);
              if (giaiNhiMatch) {
                giaiNhiGiaTri = giaiNhiMatch[1].replace(/[,\.]/g, "");
              }
            }

            if (!giaiBaGiaTri || giaiBaGiaTri === "đ") {
              const parentText = parent.textContent;
              const giaiBaMatch = parentText.match(/Giải ba[:\s]*([\d,\.]+)đ/i);
              if (giaiBaMatch) {
                giaiBaGiaTri = giaiBaMatch[1].replace(/[,\.]/g, "");
              }
            }

            // Cách 3: Thử tìm theo các selector khác (fallback)
            if (!giaiNhatGiaTri || giaiNhatGiaTri === "đ") {
              // Thử tìm theo các selector khác
              const altSelectors = [
                "td b",
                "td[class*='giai'] b",
                "td[class*='thuong'] b",
                "b[class*='giai']",
                "b[class*='thuong']"
              ];
              
              for (const selector of altSelectors) {
                const elements = parent.querySelectorAll(selector);
                if (elements.length > 0) {
                  // Chỉ log khi debug cần thiết
                  // console.log(`🔍 Found ${elements.length} elements with selector: ${selector}`);
                }
              }
            }

            // Debug: Kiểm tra các DOM elements
            const debugElements = {
              giaiThuongElementsCount: parent.querySelectorAll("td.giai_thuong_gia_tri b").length,
              giaiThuongElementsText: Array.from(parent.querySelectorAll("td.giai_thuong_gia_tri b")).map(el => el.innerText),
              parentTextLength: parent.textContent.length,
              sectionTextLength: sectionTextForJackpot.length,
              jackpotElement: parent.querySelector("b#DT6X45_G_JACKPOT")?.innerText || "not found",
              parentTextSample: parent.textContent.substring(0, 500), // Lấy 500 ký tự đầu để xem structure
              allTables: parent.querySelectorAll("table").length,
              allTds: parent.querySelectorAll("td").length,
              allBs: parent.querySelectorAll("b").length
            };

            // Return debug info cùng với result
            const debugInfo = {
              index: index,
              sectionText: sectionText.substring(0, 200),
              drawNumber,
              drawDate, 
              numbers,
              jackpot,
              giaiNhatGiaTri,
              giaiNhiGiaTri,
              giaiBaGiaTri,
              jackpot1Sl,
              giaiNhatSl,
              giaiNhiSl,
              giaiBaSl,
              numbersDebug: numbersDebug,
              debugElements: debugElements
            };

            if (drawNumber && drawDate && numbers && numbers.length === 6) {
              resultList.push({
                draw_type: "Mega645",
                draw_number: drawNumber,
                draw_date: drawDate,
                numbers: numbers.join(","),
                jackpot_amount: jackpot ? parseInt(jackpot) : 0,
                giai_nhat_gia_tri: giaiNhatGiaTri ? parseInt(giaiNhatGiaTri) : 0,
                giai_nhi_gia_tri: giaiNhiGiaTri ? parseInt(giaiNhiGiaTri) : 0,
                giai_ba_gia_tri: giaiBaGiaTri ? parseInt(giaiBaGiaTri) : 0,
                jackpot_1_sl: jackpot1Sl ? parseInt(jackpot1Sl) : 0,
                giai_nhat_sl: giaiNhatSl ? parseInt(giaiNhatSl) : 0,
                giai_nhi_sl: giaiNhiSl ? parseInt(giaiNhiSl) : 0,
                giai_ba_sl: giaiBaSl ? parseInt(giaiBaSl) : 0,
                debug: debugInfo
              });
            } else {
              // Return debug info ngay cả khi không parse được
              resultList.push({ debug: debugInfo });
            }
          });

          return resultList;
        });

         // Debug: In ra tất cả debug info
         console.log(`📊 Total results found: ${results.length}`);
         results.forEach((result, index) => {
           if (result.debug) {
             console.log(`🔍 Section ${index}:`, {
               drawNumber: result.debug.drawNumber,
               drawDate: result.debug.drawDate,
               numbers: result.debug.numbers,
               jackpot: result.debug.jackpot,
               giaiNhatGiaTri: result.debug.giaiNhatGiaTri,
               giaiNhiGiaTri: result.debug.giaiNhiGiaTri,
               giaiBaGiaTri: result.debug.giaiBaGiaTri,
               jackpot1Sl: result.debug.jackpot1Sl,
               giaiNhatSl: result.debug.giaiNhatSl,
               giaiNhiSl: result.debug.giaiNhiSl,
               giaiBaSl: result.debug.giaiBaSl,
               debugElements: result.debug.debugElements,
               numbersDebug: result.debug.numbersDebug
             });
           }
         });

        if (results.length === 0) {
          console.log("Không tìm thấy kết quả hợp lệ.");
          break;
        }

        // Lưu vào database, kiểm tra trùng draw_number
        let hasUpdates = false;
        
        for (const r of results) {
          try {
            // Kiểm tra xem draw_number đã tồn tại chưa
            const [existing] = await db.execute(
              "SELECT draw_number FROM vietlott_results WHERE draw_number = ? AND draw_type = 'Mega645'",
              [r.draw_number]
            );

            if (existing.length === 0) {
              // Nếu draw_number chưa tồn tại, lưu vào database
              await db.execute(
                `INSERT INTO vietlott_results (
                  draw_type, draw_number, draw_date, numbers, 
                  jackpot_amount,giai_nhat_gia_tri, giai_nhi_gia_tri, giai_ba_gia_tri,
                  jackpot_1_sl,giai_nhat_sl, giai_nhi_sl, giai_ba_sl          
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
                [
                  r.draw_type,
                  r.draw_number,
                  r.draw_date,
                  r.numbers,
                  r.jackpot_amount,
                  r.giai_nhat_gia_tri,
                  r.giai_nhi_gia_tri,
                  r.giai_ba_gia_tri,
                  r.jackpot_1_sl,
                  r.giai_nhat_sl,
                  r.giai_nhi_sl,
                  r.giai_ba_sl,
                ]
              );
              console.log(`✅ Đã lưu kỳ #${r.draw_number} (${r.draw_date})`);
              hasUpdates = true;
            } else {
              console.log(
                `ℹ️ Kỳ #${r.draw_number} (${r.draw_date}) đã tồn tại trong database. Đang cập nhật...`
              );
              
              // Update existing record
              await db.execute(
                `UPDATE vietlott_results SET 
                  numbers = ?, jackpot_amount = ?, 
                  giai_nhat_gia_tri = ?, giai_nhi_gia_tri = ?, giai_ba_gia_tri = ?,
                  jackpot_1_sl = ?, giai_nhat_sl = ?, giai_nhi_sl = ?, giai_ba_sl = ?
                  WHERE draw_type = ? AND draw_number = ? AND draw_date = ?`,
                [
                  r.numbers,
                  r.jackpot_amount,
                  r.giai_nhat_gia_tri,
                  r.giai_nhi_gia_tri,
                  r.giai_ba_gia_tri,
                  r.jackpot_1_sl,
                  r.giai_nhat_sl,
                  r.giai_nhi_sl,
                  r.giai_ba_sl,
                  r.draw_type,
                  r.draw_number,
                  r.draw_date
                ]
              );
              console.log(`✅ Đã cập nhật kỳ #${r.draw_number} (${r.draw_date})`);
              hasUpdates = true;
            }
          } catch (dbErr) {
            console.error(`Lỗi lưu kỳ #${r.draw_number}:`, dbErr.message);
          }
        }
        
        // Chỉ xóa cache và update cache một lần sau khi cào xong tất cả
        if (hasUpdates) {
          console.log("🔄 Đang xóa cache và update cache...");
          await clearCache(db);
          await updateCacheByUrls();
          console.log("✅ Hoàn thành xóa cache và update cache");
        } else {
          console.log("ℹ️ Không có cập nhật nào, bỏ qua xóa cache");
        }

        break;
      } catch (e) {
        console.error("Lỗi:", e.message);
        retries++;
        if (retries < maxRetries) {
          console.log("Thử lại lần", retries + 1);
        } else {
          console.log("Hủy bỏ sau 3 lần thử");
        }
      } finally {
        if (browser) await browser.close();
      }
    }
  } catch (e) {
    console.error("Lỗi kết nối DB:", e.message);
  } finally {
    await db.end();
  }
})();

/**
 * Clear related cache
 */
async function clearCache(db) {
  console.log("🗑️ Đang xóa cache...");
  
  const cacheKeys = [
    'homepage_index',
    'MEGA_home_mega_latest_v1',
    'MEGA_frequency_limit20_orderASC',
    'MEGA_frequency_limit20_orderDESC',
    'MEGA_latest',
    'MEGA_patterns',
    'Mega645_statistics_limit100',
  ];

  try {
    // Kiểm tra xem bảng cache có tồn tại không
    try {
      await db.execute('SELECT 1 FROM cache LIMIT 1');
      console.log("ℹ️ Cache table exists, proceeding with cache deletion");
      
      // Xóa cache từ database cache table
      for (const key of cacheKeys) {
        try {
          const result = await db.execute('DELETE FROM cache WHERE key_name = ?', [key]);
          if (result[0].affectedRows > 0) {
            console.log(`🗑️ Deleted cache key: ${key}`);
          } else {
            console.log(`ℹ️ Cache key not found: ${key}`);
          }
        } catch (err) {
          console.log(`⚠️ Cache key ${key} deletion failed:`, err.message);
        }
      }
      
      // Xóa cache controller Vietlott645
      await clearVietlottControllerCache(db);
    } catch (tableErr) {
      if (tableErr.message.includes("doesn't exist")) {
        console.log("ℹ️ Cache table doesn't exist, skipping database cache deletion");
      } else {
        console.log("⚠️ Error checking cache table:", tableErr.message);
      }
    }
    
    // Xóa cache bằng Redis nếu có
    await clearRedisCache();
    
    console.log("✅ Đã xóa cache");
  } catch (err) {
    console.error("❌ Error clearing cache:", err.message);
  }
}

/**
 * Clear Vietlott645 controller cache
 */
async function clearVietlottControllerCache(db) {
  try {
    const patterns = [
      'models_Mega645_index',
      'models_Mega645_results',
      'models_Mega645_byDate',
      'models_MEGA_',
      'models_Mega645_'
    ];
    
    for (const pattern of patterns) {
      try {
        const result = await db.execute('DELETE FROM cache WHERE key_name LIKE ?', [`${pattern}%`]);
        if (result[0].affectedRows > 0) {
          console.log(`🗑️ Deleted cache pattern: ${pattern}* (${result[0].affectedRows} keys)`);
        } else {
          console.log(`ℹ️ No cache found for pattern: ${pattern}*`);
        }
      } catch (err) {
        console.log(`⚠️ Cache pattern ${pattern} deletion failed:`, err.message);
      }
    }
    
    console.log("✅ Vietlott645 Controller cache cleared");
  } catch (err) {
    console.error("❌ Error clearing controller cache:", err.message);
  }
}

/**
 * Clear Redis cache if available
 */
async function clearRedisCache() {
  try {
    // Sử dụng API chuyên dụng cho Mega645 cache
    const baseUrl = "http://127.0.0.1";
    const redisApiUrl = `${baseUrl}/api/redis/clear-mega645-cache.html`;
    
    console.log("🔄 Đang xóa Redis cache qua API...");
    
    const response = await axios.post(redisApiUrl, {}, {
      timeout: 10000,
      httpsAgent: new (require('https').Agent)({
        rejectUnauthorized: false
      }),
      headers: {
        'Content-Type': 'application/json',
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
      }
    });
    
    if (response.status === 200) {
      const data = response.data;
      console.log(`✅ Redis cache cleared via API: ${data.message}`);
      console.log(`📊 Total deleted: ${data.total_deleted} keys`);
      return true;
    } else {
      console.log(`❌ Redis cache clear failed: HTTP ${response.status}`);
      return false;
    }
  } catch (err) {
    console.log("ℹ️ Redis API not available or error:", err.message);
    
    // Fallback: Thử API xóa cache thông thường
    try {
      const baseUrl = "http://127.0.0.1";
      const redisApiUrl = `${baseUrl}/api/redis/clear-cache.html`;
      
      const response = await axios.post(redisApiUrl, {
        keys: [
          'MEGA_home_mega_latest_v1',
          'MEGA_frequency_limit20_orderASC',
          'MEGA_frequency_limit20_orderDESC',
          'MEGA_latest',
          'MEGA_patterns',
          'Mega645_statistics_limit100',
        ]
      }, {
        timeout: 10000,
        httpsAgent: new (require('https').Agent)({
          rejectUnauthorized: false
        }),
        headers: {
          'Content-Type': 'application/json',
          'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }
      });
      
      if (response.status === 200) {
        const data = response.data;
        console.log(`✅ Redis cache cleared via fallback API: ${data.message}`);
        console.log(`📊 Deleted: ${data.deleted_count}/${data.total_keys} keys`);
        return true;
      }
    } catch (fallbackErr) {
      console.log("ℹ️ Fallback Redis API also failed:", fallbackErr.message);
    }
  }
}

/**
 * Update cache by calling URLs
 */
async function updateCacheByUrls() {
  try {
    const baseUrl = "http://127.0.0.1";
    
    // Generate prediction URL for today
    const today = new Date();
    const formattedDate = today.toISOString().split('T')[0];
    const urldudoan = `/du-doan-soi-cau-xo-so-mega-6-45-vietlott-ngay-${formattedDate}-co-nen-xuong-tay`;
    
    const urls = [
      baseUrl,
      baseUrl + '/ket-qua-xoso-mega-6-45-vietlott-' + formattedDate.split('-').reverse().join('-') + '.html',
      baseUrl + '/thong-ke-xo-so-mega-6-45.html',
      baseUrl + urldudoan + '.html',
    ];
    
    for (const url of urls) {
      await updateCacheByUrl(url);
    }
  } catch (err) {
    console.error("❌ Error updating cache URLs:", err.message);
  }
}

/**
 * Update single cache URL
 */
async function updateCacheByUrl(url) {
  try {
    const response = await axios.get(url, {
      timeout: 30000,
      httpsAgent: new (require('https').Agent)({
        rejectUnauthorized: false // Bỏ qua SSL certificate
      }),
      headers: {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language': 'vi-VN,vi;q=0.8,en-US;q=0.5,en;q=0.3',
        'Connection': 'keep-alive',
      }
    });
    
    if (response.status === 200) {
      console.log(`✅ Cache updated: ${url}`);
      return true;
    } else {
      console.log(`❌ Cache update failed: ${url} (HTTP: ${response.status})`);
      return false;
    }
  } catch (err) {
    console.error(`❌ Error updating cache URL ${url}:`, err.message);
    return false;
  }
}
