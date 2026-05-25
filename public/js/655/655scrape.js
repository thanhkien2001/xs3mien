const puppeteer = require("puppeteer");
const mysql = require("mysql2/promise");
const fs = require("fs");
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
        browser = await puppeteer.launch({
          headless: true,
          args: [
            "--no-sandbox",
            "--disable-setuid-sandbox",
            "--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
          ],
        });

        const page = await browser.newPage();
        await page.setExtraHTTPHeaders({
          Accept:
            "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
          "Accept-Language": "en-US,en;q=0.5",
          Referer: "https://www.minhngoc.net.vn/",
        });

        await page.goto(
          "https://www.minhngoc.net.vn/ket-qua-xo-so/dien-toan-vietlott/power-6x55.html",
          {
            waitUntil: "networkidle2",
            timeout: 120000,
          }
        );

        await page.waitForSelector("div.bangkq6x55", { timeout: 60000 });

        const results = await page.evaluate(() => {
          const resultList = [];

          const resultBoxes = document.querySelectorAll(
            "div.box-result-detail"
          );

          resultBoxes.forEach((box) => {
            // Lấy thông tin kỳ quay
            const infoText =
              box.querySelector("td[align='center']")?.innerText || "";
            const drawNumberMatch = infoText.match(/#(\d+)/);
            const drawDateMatch = infoText.match(
              /quay thưởng\s+(\d{2}\/\d{2}\/\d{4})/i
            );

            const drawNumber = drawNumberMatch ? drawNumberMatch[1].replace(/^0+/, "") : null;
            const drawDate = drawDateMatch
              ? drawDateMatch[1].split("/").reverse().join("-")
              : null;

            // Lấy số trúng
            const numberDivs = box.querySelectorAll(
              "ul.result-number div[class^='finnish']"
            );
            const numbers = Array.from(numberDivs)
              .map((el) => el.innerText.trim())
              .filter(Boolean);
            const specialNumber = numbers.pop();
            if (specialNumber) numbers.push(specialNumber);

            // Các giải
            const parent = box.closest("div.boxkqxsdientoan") || document; // fallback
            const jackpot = parent
              .querySelector("b#DT6X55_G_JACKPOT")
              ?.innerText.replace(/[^\d]/g, "");
            const jackpot1Sl = parent
              .querySelector("td#DT6X55_S_JACKPOT")
              ?.innerText.replace(/,/g, "");
            const jackpot2Sl = parent
              .querySelector("td#DT6X55_S_JACKPOT2")
              ?.innerText.replace(/,/g, "");
            const giaiNhatSl = parent
              .querySelector("td#DT6X55_S_G1")
              ?.innerText.replace(/,/g, "");
            const giaiNhiSl = parent
              .querySelector("td#DT6X55_S_G2")
              ?.innerText.replace(/,/g, "");
            const giaiBaSl = parent
              .querySelector("td#DT6X55_S_G3")
              ?.innerText.replace(/,/g, "");

            const jackpot2GiaTri = parent
              .querySelector("b#DT6X55_G_JACKPOT2")
              ?.innerText.replace(/[^\d]/g, "");

            const giaiTriElements = parent.querySelectorAll(
              "td.giai_thuong_gia_tri b"
            );
            const giaiNhatGiaTri = giaiTriElements[2]?.innerText.replace(
              /[^\d]/g,
              ""
            );
            const giaiNhiGiaTri = giaiTriElements[3]?.innerText.replace(
              /[^\d]/g,
              ""
            );
            const giaiBaGiaTri = giaiTriElements[4]?.innerText.replace(
              /[^\d]/g,
              ""
            );

            if (
              drawNumber &&
              drawDate &&
              numbers.length === 7 &&
              jackpot &&
              jackpot1Sl &&
              jackpot2Sl &&
              giaiNhatSl &&
              giaiNhiSl &&
              giaiBaSl &&
              jackpot2GiaTri &&
              giaiNhatGiaTri &&
              giaiNhiGiaTri &&
              giaiBaGiaTri
            ) {
              resultList.push({
                draw_type: "Power655",
                draw_number: drawNumber,
                draw_date: drawDate,
                numbers: numbers.join(","),
                jackpot_amount: parseInt(jackpot),
                jackpot_1_sl: parseInt(jackpot1Sl),
                jackpot_2_sl: parseInt(jackpot2Sl),
                giai_nhat_sl: parseInt(giaiNhatSl),
                giai_nhi_sl: parseInt(giaiNhiSl),
                giai_ba_sl: parseInt(giaiBaSl),
                giai_nhat_gia_tri: parseInt(giaiNhatGiaTri),
                giai_nhi_gia_tri: parseInt(giaiNhiGiaTri),
                giai_ba_gia_tri: parseInt(giaiBaGiaTri),
                jackpot_2: parseInt(jackpot2GiaTri),
              });
            }
          });

          return resultList;
        });



        if (results.length === 0) {
          console.log("Không tìm thấy kết quả hợp lệ cho Power655.");
          fs.appendFileSync(
            "scrape_power655.log",
            `${new Date().toISOString()}: Không tìm thấy kết quả hợp lệ cho Power655.\n`
          );
          break;
        }

        // Lưu vào database, kiểm tra trùng draw_number
        for (const r of results) {
          try {
            // Kiểm tra xem draw_number đã tồn tại chưa
            const [existing] = await db.execute(
              "SELECT draw_number FROM vietlott_results WHERE draw_number = ? AND draw_type = 'Power655'",
              [r.draw_number]
            );

            if (existing.length === 0) {
              // Nếu draw_number chưa tồn tại, lưu vào database
              await db.execute(
                `INSERT INTO vietlott_results (
                  draw_type, draw_number, draw_date, numbers, 
                  jackpot_amount, jackpot_1_sl, jackpot_2_sl, 
                  giai_nhat_sl, giai_nhi_sl, giai_ba_sl,
                  giai_nhat_gia_tri, giai_nhi_gia_tri, giai_ba_gia_tri,
                  jackpot_2
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
                [
                  r.draw_type,
                  r.draw_number,
                  r.draw_date,
                  r.numbers,
                  r.jackpot_amount,
                  r.jackpot_1_sl,
                  r.jackpot_2_sl,
                  r.giai_nhat_sl,
                  r.giai_nhi_sl,
                  r.giai_ba_sl,
                  r.giai_nhat_gia_tri,
                  r.giai_nhi_gia_tri,
                  r.giai_ba_gia_tri,
                  r.jackpot_2,
                ]
              );
              console.log(
                `Đã lưu kỳ #${r.draw_number} (${r.draw_date}) cho Power655`
              );
              fs.appendFileSync(
                "scrape_power655.log",
                `${new Date().toISOString()}: Đã lưu kỳ #${r.draw_number} (${
                  r.draw_date
                }) cho Power655\n`
              );
            } else {
              console.log(
                `Kỳ #${r.draw_number} (${r.draw_date}) đã tồn tại trong database cho Power655.`
              );
              fs.appendFileSync(
                "scrape_power655.log",
                `${new Date().toISOString()}: Kỳ #${r.draw_number} (${
                  r.draw_date
                }) đã tồn tại\n`
              );
            }
          } catch (dbErr) {
            console.error(`Lỗi lưu kỳ #${r.draw_number}:`, dbErr.message);
            fs.appendFileSync(
              "scrape_power655.log",
              `${new Date().toISOString()}: Lỗi lưu kỳ #${r.draw_number}: ${
                dbErr.message
              }\n`
            );
          }
        }

        break;
      } catch (e) {
        console.error("Lỗi khi cào Power655:", e.message);
        fs.appendFileSync(
          "scrape_power655.log",
          `${new Date().toISOString()}: Lỗi khi cào Power655: ${e.message}\n`
        );
        retries++;
        if (retries < maxRetries) {
          console.log(`Thử lại lần ${retries + 1} cho Power655`);
        } else {
          console.log("Hủy bỏ sau 3 lần thử cho Power655");
        }
      } finally {
        if (browser) await browser.close();
      }
    }
  } catch (e) {
    console.error("Lỗi kết nối DB:", e.message);
    fs.appendFileSync(
      "scrape_power655.log",
      `${new Date().toISOString()}: Lỗi kết nối DB: ${e.message}\n`
    );
  } finally {
    await db.end();
  }
})();
