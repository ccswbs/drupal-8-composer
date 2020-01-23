using Microsoft.Extensions.Configuration;
using Microsoft.VisualStudio.TestTools.UnitTesting;
using Test;

namespace BoveyTest
{
    [TestClass]
    public class UnitTest1 : DrupalTest
    {
        [TestInitialize]
        [DeploymentItem("appsettings.json")]
        public void Initialize()
        {
            var config = new ConfigurationBuilder()
                .AddJsonFile("appsettings.json")
                .Build();
            base.Initialize(config["hostname"], config["basePath"]);
            DrupalLogin(config["username"], config["password"]);
        }

        [TestMethod]
        public void TestMethod1()
        {
            DrupalGet();
        }
    }
}
