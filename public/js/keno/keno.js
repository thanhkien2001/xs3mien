const puppeteer = require("puppeteer");
const mysql = require("mysql2/promise");

(async () => {
  // Kết nối database

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
          Accept: "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
          "Accept-Language": "en-US,en;q=0.5",
          Referer: "https://atrungroi.com/",
        });

        await page.goto("https://atrungroi.com/vietlott-keno.html", {
          waitUntil: "networkidle2",
          timeout: 120000,
        });

        try {
          await page.waitForSelector("div.js-keno-item, div.js-keno-latest", {
            timeout: 60000,
          });
        } catch (e) {
          const html = await page.content();
          throw e;
        }

        const results = await page.evaluate(() => {
          const items = document.querySelectorAll("div.js-keno-item, div.js-keno-latest");
          const resultList = [];
          items.forEach((item) => {
            // Lấy kỳ quay
            const drawNumberElement = item.querySelector("span.text-red");
            const drawNumber = drawNumberElement ? drawNumberElement.innerText.trim() : null;
            const drawDateElement = item.querySelector("span.js-data-full-time");
            const drawDateText = drawDateElement ? drawDateElement.innerText.trim() : null;
            let drawDate = null;
            if (drawDateText) {
              const [time, date] = drawDateText.split(" ");
              const [hour, minute] = time.split(":");
              const [day, month, year] = date.split("/");
              drawDate = `${year}-${month}-${day} ${hour}:${minute}:00`;
            }
            const numbers = Array.from(item.querySelectorAll("span.ball.keno"))
              .map((el) => el.innerText.trim())
              .filter((num) => num && /^\d{2}$/.test(num));
            let evenCount = 0, oddCount = 0, largeCount = 0, smallCount = 0;
            numbers.forEach(num => {
              const n = parseInt(num);
              if (n % 2 === 0) evenCount++;
              else oddCount++;
              if (n >= 41 && n <= 80) largeCount++;
              else if (n >= 1 && n <= 40) smallCount++;
            });

            if (
              drawNumber &&
              drawDate &&
              numbers.length === 20 &&
              evenCount + oddCount === 20 &&
              largeCount + smallCount === 20
            ) {
              resultList.push({
                draw_number: drawNumber,
                draw_date: drawDate,
                numbers: numbers.join(","),
                even_count: evenCount,
                odd_count: oddCount,
                large_count: largeCount,
                small_count: smallCount,
              });
            }
          });

          return resultList;
        });

        if (results.length === 0) {
          const html = await page.content();
          break;
        }

        // Lưu vào database
        for (const r of results) {
          try {
            const [existing] = await db.execute(
              "SELECT draw_number FROM keno_results WHERE draw_number = ?",
              [r.draw_number]
            );

            if (existing.length === 0) {
              await db.execute(
                `INSERT INTO keno_results (
                  draw_number, draw_date, numbers, 
                  even_count, odd_count, large_count, small_count
                ) VALUES (?, ?, ?, ?, ?, ?, ?)`,
                [
                  r.draw_number,
                  r.draw_date,
                  r.numbers,
                  r.even_count,
                  r.odd_count,
                  r.large_count,
                  r.small_count,
                ]
              );
            } else {
            }
          } catch (dbErr) {
          }
        }

        break;
      } catch (e) {
        retries++;
        if (retries < maxRetries) {
        } else {
        }
      } finally {
        if (browser) await browser.close();
      }
    }
  } catch (e) {
  } finally {
    await db.end();
  }
})();
