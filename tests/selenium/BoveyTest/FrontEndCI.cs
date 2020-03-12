using System;
using Microsoft.Extensions.Configuration;
using Microsoft.VisualStudio.TestTools.UnitTesting;
using Test;

namespace BoveyTest
{
    [TestClass]
    public class FrontEndCI : DrupalTest
    {
        [TestInitialize]
        [DeploymentItem("appsettings*.json")]
        public void Initialize()
        {
            var config = new ConfigurationBuilder()
                .AddJsonFile("appsettings.json")
                .AddJsonFile("appsettings.local.json", optional:true)
                .Build();
            base.Initialize(config["TestQAHostname"], config["basePath"]);
            DrupalLogin(config["TestQAUsername"], config["TestQAPassword"]);
        }

        [TestCleanup]
        override public void Cleanup()
        {
            _driver.Quit();
        }

        [TestMethod]
        public void BuildTriggerSuccessful()
        {
            var frontEndEnv = "stage_gatsby_frontend_environment";
            var buildTriggerSubmitBtn = "edit-submit";
            
            // Go to Build Trigger deployment page
            DrupalGet("/admin/build_hooks/deployments/" + frontEndEnv);

            // Activate build trigger
            Click(buildTriggerSubmitBtn);

            // Check if successful message appears after testing connection
            var successfulBuildTriggerMessage = Driver.FindElementsByXPath($"//div[contains(@class, 'messages--status') and text()[contains(.,'Deployment triggered for environment')]]");
            Assert.AreEqual(successfulBuildTriggerMessage.Count, 1);
        }
    }
}
